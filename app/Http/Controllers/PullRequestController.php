<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Issue;
use App\Models\Project;
use App\Models\PullRequest;
use App\Models\PullRequestComment;
use App\Models\PullRequestReview;
use App\Models\Repository;
use App\Services\VcsService;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PullRequestController extends Controller
{
    public function index(Request $request, Repository $repository)
    {
        $this->authorizeRole($request, $repository->project, Roles::OBSERVER);

        return PullRequest::where('repository_id', $repository->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->with(['author:id,name', 'reviewers:id,name'])
            ->withCount('comments')
            ->orderByDesc('number')
            ->paginate(30);
    }

    public function store(Request $request, Repository $repository, VcsService $vcs): JsonResponse
    {
        $project = $repository->project;
        $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:20000',
            'source_branch_id' => 'required|integer',
            'target_branch_id' => 'required|integer|different:source_branch_id',
            'reviewer_ids' => 'nullable|array|max:20',
            'reviewer_ids.*' => 'integer|exists:users,id',
            'issue_ids' => 'nullable|array|max:50',
            'issue_ids.*' => 'integer',
        ]);

        $source = Branch::where('repository_id', $repository->id)->findOrFail($data['source_branch_id']);
        $target = Branch::where('repository_id', $repository->id)->findOrFail($data['target_branch_id']);
        $user = $request->user();

        abort_unless($source->head_commit_id, 422, 'La rama origen no tiene commits.');

        $duplicate = PullRequest::where('repository_id', $repository->id)
            ->where('source_branch_id', $source->id)
            ->where('target_branch_id', $target->id)
            ->whereIn('status', PullRequest::OPEN_STATES)
            ->exists();
        abort_if($duplicate, 422, 'Ya existe un Pull Request abierto entre estas dos ramas.');

        if ($target->head_commit_id) {
            $included = isset($vcs->ancestors($vcs->graph($repository->id), $target->head_commit_id)[$source->head_commit_id]);
            abort_if($included, 422, 'La rama origen no aporta cambios nuevos a la rama destino.');
        }

        $reviewerIds = array_values(array_unique(array_diff($data['reviewer_ids'] ?? [], [$user->id])));
        foreach ($reviewerIds as $reviewerId) {
            $this->ensureMember($project, $reviewerId);
        }

        $issueIds = Issue::where('repository_id', $repository->id)->whereIn('id', $data['issue_ids'] ?? [])->pluck('id')->all();

        $pr = DB::transaction(function () use ($repository, $data, $source, $target, $user, $project, $reviewerIds, $issueIds) {
            $number = (int) PullRequest::where('repository_id', $repository->id)->lockForUpdate()->max('number') + 1;

            $pr = PullRequest::create([
                'repository_id' => $repository->id,
                'number' => $number,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'author_id' => $user->id,
                'source_branch_id' => $source->id,
                'target_branch_id' => $target->id,
                'source_branch_name' => $source->name,
                'target_branch_name' => $target->name,
                'status' => $reviewerIds ? 'in_review' : 'open',
            ]);

            $pr->reviewers()->sync($reviewerIds);
            $pr->issues()->sync($issueIds);

            Activity::log($project, $user, 'pull_request.opened', $pr, [
                'number' => $pr->number,
                'title' => $pr->title,
                'from' => $source->name,
                'to' => $target->name,
            ]);

            return $pr;
        });

        return response()->json($this->loadFull($pr), 201);
    }

    public function show(Request $request, PullRequest $pullRequest, VcsService $vcs): JsonResponse
    {
        $this->authorizeRole($request, $pullRequest->repository->project, Roles::OBSERVER);

        $payload = $this->loadFull($pullRequest)->toArray();
        $payload['files'] = $this->files($pullRequest, $vcs);

        $payload['mergeable'] = null;
        $payload['conflicts'] = [];

        $source = $pullRequest->sourceBranch;
        $target = $pullRequest->targetBranch;
        if ($pullRequest->isOpen() && $source?->head_commit_id && $target) {
            $plan = $vcs->planMerge($vcs->graph($pullRequest->repository_id), $target->head_commit_id, $source->head_commit_id);
            $payload['mergeable'] = $plan['conflicts'] === [];
            $payload['conflicts'] = $plan['conflicts'];
        }

        return response()->json($payload);
    }

    protected function files(PullRequest $pullRequest, VcsService $vcs): array
    {
        if ($pullRequest->status === 'merged' && $pullRequest->merge_commit_id) {
            $merge = \App\Models\Commit::find($pullRequest->merge_commit_id);

            return $vcs->diff($merge?->parent_id, $merge?->id);
        }

        $s = $pullRequest->sourceBranch?->head_commit_id;
        $t = $pullRequest->targetBranch?->head_commit_id;

        if (! $s) {
            return [];
        }

        $base = $t ? $vcs->mergeBase($vcs->graph($pullRequest->repository_id), $t, $s) : null;

        return $vcs->diff($base, $s);
    }

    public function filesEndpoint(Request $request, PullRequest $pullRequest, VcsService $vcs): JsonResponse
    {
        $this->authorizeRole($request, $pullRequest->repository->project, Roles::OBSERVER);

        return response()->json($this->files($pullRequest, $vcs));
    }

    public function update(Request $request, PullRequest $pullRequest): JsonResponse
    {
        $project = $pullRequest->repository->project;
        $role = $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        abort_unless($pullRequest->author_id === $request->user()->id || $role === Roles::OWNER, 403, 'Solo el autor o el propietario pueden editar el Pull Request.');
        abort_unless($pullRequest->isOpen(), 422, 'El Pull Request ya está cerrado.');

        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:20000',
            'reviewer_ids' => 'nullable|array|max:20',
            'reviewer_ids.*' => 'integer|exists:users,id',
        ]);

        if (array_key_exists('reviewer_ids', $data)) {
            $ids = array_values(array_unique(array_diff($data['reviewer_ids'] ?? [], [$pullRequest->author_id])));
            foreach ($ids as $id) {
                $this->ensureMember($project, $id);
            }
            $pullRequest->reviewers()->sync($ids);
            if ($pullRequest->status === 'open' && $ids) {
                $pullRequest->status = 'in_review';
            }
        }

        $pullRequest->fill(collect($data)->only(['title', 'description'])->all())->save();

        return response()->json($this->loadFull($pullRequest));
    }

    public function storeComment(Request $request, PullRequest $pullRequest): JsonResponse
    {
        $project = $pullRequest->repository->project;
        $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        $data = $request->validate([
            'body' => 'required|string|max:10000',
            'path' => 'nullable|string|max:255',
            'line' => 'nullable|integer|min:1',
        ]);

        $comment = PullRequestComment::create($data + [
            'pull_request_id' => $pullRequest->id,
            'user_id' => $request->user()->id,
        ]);

        Activity::log($project, $request->user(), 'pull_request.commented', $pullRequest, ['number' => $pullRequest->number]);

        return response()->json($comment->load('user:id,name'), 201);
    }

    public function storeReview(Request $request, PullRequest $pullRequest): JsonResponse
    {
        $project = $pullRequest->repository->project;
        $role = $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        abort_unless($pullRequest->isOpen(), 422, 'El Pull Request ya está cerrado.');

        $data = $request->validate([
            'state' => ['required', Rule::in(['approved', 'changes_requested', 'commented'])],
            'body' => 'nullable|string|max:10000|required_if:state,changes_requested',
        ]);

        $user = $request->user();

        if ($data['state'] === 'approved') {
            abort_if($pullRequest->author_id === $user->id && $role !== Roles::OWNER, 422, 'No puedes aprobar tu propio Pull Request.');
        }

        $review = PullRequestReview::create($data + ['pull_request_id' => $pullRequest->id, 'user_id' => $user->id]);

        $pullRequest->update(['status' => $this->computeStatus($pullRequest)]);

        Activity::log($project, $user, "pull_request.{$data['state']}", $pullRequest, ['number' => $pullRequest->number]);

        return response()->json(array_merge($review->load('user:id,name')->toArray(), [
            'pull_request_status' => $pullRequest->status,
        ]), 201);
    }

    public function merge(Request $request, PullRequest $pullRequest, VcsService $vcs): JsonResponse
    {
        $project = $pullRequest->repository->project;
        $role = $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        abort_unless($pullRequest->isOpen(), 422, 'El Pull Request ya está cerrado.');
        abort_if($pullRequest->status === 'changes_requested', 422, 'Hay cambios solicitados pendientes de resolver.');
        abort_unless(
            $pullRequest->status === 'approved' || $role === Roles::OWNER,
            422,
            'El Pull Request necesita al menos una aprobación para fusionarse.'
        );

        $user = $request->user();
        $commit = $vcs->merge($pullRequest, $user);

        $pullRequest->update([
            'status' => 'merged',
            'merged_by' => $user->id,
            'merged_at' => now(),
            'merge_commit_id' => $commit->id,
        ]);

        // Las issues vinculadas pasan a "resuelta"
        $pullRequest->issues()->whereIn('status', ['open', 'in_progress'])->update(['status' => 'resolved']);

        Activity::log($project, $user, 'pull_request.merged', $pullRequest, [
            'number' => $pullRequest->number,
            'from' => $pullRequest->source_branch_name,
            'to' => $pullRequest->target_branch_name,
        ]);

        return response()->json($this->loadFull($pullRequest->fresh()));
    }

    public function close(Request $request, PullRequest $pullRequest): JsonResponse
    {
        $project = $pullRequest->repository->project;
        $role = $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        abort_unless($pullRequest->author_id === $request->user()->id || $role === Roles::OWNER, 403, 'Solo el autor o el propietario pueden cerrar el Pull Request.');
        abort_unless($pullRequest->isOpen(), 422, 'El Pull Request ya está cerrado.');

        $pullRequest->update(['status' => 'closed']);
        Activity::log($project, $request->user(), 'pull_request.closed', $pullRequest, ['number' => $pullRequest->number]);

        return response()->json($this->loadFull($pullRequest));
    }

    /** Estado derivado de la última revisión decisiva de cada revisor. */
    private function computeStatus(PullRequest $pullRequest): string
    {
        $latest = $pullRequest->reviews()->orderBy('id')->get()
            ->groupBy('user_id')
            ->map(fn ($reviews) => $reviews->whereIn('state', ['approved', 'changes_requested'])->last()?->state)
            ->filter()
            ->values();

        if ($latest->contains('changes_requested')) {
            return 'changes_requested';
        }
        if ($latest->contains('approved')) {
            return 'approved';
        }

        return 'in_review';
    }

    private function loadFull(PullRequest $pr): PullRequest
    {
        return $pr->load([
            'author:id,name',
            'reviewers:id,name',
            'reviews.user:id,name',
            'comments.user:id,name',
            'issues:id,number,title,status',
            'sourceBranch:id,name,head_commit_id',
            'targetBranch:id,name,head_commit_id',
            'activity.user:id,name',
        ]);
    }
}

