<?php

namespace App\Http\Controllers;

use App\Models\PullRequest;
use App\Models\Repository;
use App\Services\PullRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PullRequestController extends Controller
{
    public function __construct(private PullRequestService $pullRequests) {}

    public function index(Request $request, Repository $repository)
    {
        Gate::authorize('view', $repository);

        return PullRequest::where('repository_id', $repository->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->with(['author:id,name', 'reviewers:id,name'])
            ->withCount('comments')
            ->orderByDesc('number')
            ->paginate(30);
    }

    public function store(Request $request, Repository $repository): JsonResponse
    {
        Gate::authorize('create', [PullRequest::class, $repository]);

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

        $pr = $this->pullRequests->open($repository, $request->user(), [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'source_branch_id' => (int) $data['source_branch_id'],
            'target_branch_id' => (int) $data['target_branch_id'],
            'reviewers' => $data['reviewer_ids'] ?? [],
            'issues' => $data['issue_ids'] ?? [],
        ]);

        return response()->json($this->loadFull($pr), 201);
    }

    public function show(PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('view', $pullRequest->repository);

        $payload = $this->loadFull($pullRequest)->toArray();
        $payload['files'] = $this->pullRequests->files($pullRequest);

        return response()->json(array_merge($payload, $this->pullRequests->mergeability($pullRequest)));
    }

    public function filesEndpoint(PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('view', $pullRequest->repository);

        return response()->json($this->pullRequests->files($pullRequest));
    }

    public function update(Request $request, PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('update', $pullRequest);

        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:20000',
            'reviewer_ids' => 'nullable|array|max:20',
            'reviewer_ids.*' => 'integer|exists:users,id',
        ]);

        if (array_key_exists('reviewer_ids', $data)) {
            $data['reviewers'] = $data['reviewer_ids'] ?? [];
            unset($data['reviewer_ids']);
        }

        $pr = $this->pullRequests->update($pullRequest, $request->user(), $data);

        return response()->json($this->loadFull($pr));
    }

    public function comments(PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('view', $pullRequest->repository);

        // Paginados (30) del más antiguo al más reciente
        return response()->json($pullRequest->comments()->with('user:id,name')->orderBy('id')->paginate(30));
    }

    /** Comentario general (cualquier miembro) o sobre una línea del código (no observadores). */
    public function storeComment(Request $request, PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('comment', $pullRequest);

        $data = $request->validate([
            'body' => 'required|string|max:10000',
            'path' => 'nullable|string|max:255',
            'line' => 'nullable|integer|min:1',
        ]);

        $onCode = isset($data['path']) || isset($data['line']);
        if ($onCode) {
            Gate::authorize('commentOnCode', $pullRequest);
        }

        $comment = $this->pullRequests->comment(
            $pullRequest,
            $request->user(),
            $data['body'],
            $data['path'] ?? null,
            isset($data['line']) ? (int) $data['line'] : null,
        );

        return response()->json($comment->load('user:id,name'), 201);
    }

    public function storeReview(Request $request, PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('review', $pullRequest);

        $data = $request->validate([
            'state' => ['required', Rule::in(PullRequestService::REVIEW_STATES)],
            'body' => 'nullable|string|max:10000|required_if:state,changes_requested',
        ]);

        $review = $this->pullRequests->review($pullRequest, $request->user(), $data['state'], $data['body'] ?? null);

        return response()->json(array_merge($review->load('user:id,name')->toArray(), [
            'pull_request_status' => $pullRequest->status,
        ]), 201);
    }

    public function merge(Request $request, PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('merge', $pullRequest);

        $this->pullRequests->merge($pullRequest, $request->user());

        return response()->json($this->loadFull($pullRequest->fresh()));
    }

    public function close(Request $request, PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('close', $pullRequest);

        return response()->json($this->loadFull($this->pullRequests->close($pullRequest, $request->user())));
    }

    public function reopen(Request $request, PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('close', $pullRequest);

        return response()->json($this->loadFull($this->pullRequests->reopen($pullRequest, $request->user())));
    }

    private function loadFull(PullRequest $pr): PullRequest
    {
        return $pr->load([
            'author:id,name',
            'reviewers:id,name',
            'reviews.user:id,name',
            // Solo los 20 comentarios más recientes; el resto en /comments (paginado)
            'comments' => fn ($q) => $q->with('user:id,name')->latest('id')->limit(20),
            'issues:id,number,title,status',
            'sourceBranch:id,name,head_commit_id',
            'targetBranch:id,name,head_commit_id',
            'activity' => fn ($q) => $q->with('user:id,name')->latest('id')->limit(50),
        ])->loadCount('comments');
    }

    /** Conflictos con detalle (versiones de cada lado y texto con marcadores). */
    public function conflicts(PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('push', $pullRequest->repository);

        return response()->json([
            'source_head' => $pullRequest->sourceBranch?->head_commit_id,
            'target_head' => $pullRequest->targetBranch?->head_commit_id,
            'files' => $this->pullRequests->conflicts($pullRequest),
        ]);
    }

    /** Resuelve: files = [{path, action: edit|delete, content}] → commit de fusión en la rama origen. */
    public function resolve(Request $request, PullRequest $pullRequest): JsonResponse
    {
        Gate::authorize('push', $pullRequest->repository);

        $data = $request->validate([
            'files' => 'required|array',
            'files.*.path' => 'required|string',
            'files.*.action' => 'required|in:edit,delete,ours,theirs',
            'files.*.content' => 'nullable|string',
            'files.*.eol' => 'nullable|in:lf,crlf',
            'source_head' => 'nullable|integer',
            'target_head' => 'nullable|integer',
        ]);

        $commit = $this->pullRequests->resolveConflicts(
            $pullRequest, $request->user(),
            \App\Http\Controllers\Web\Collab\PullRequestController::resolutions($data['files']),
            array_filter(['source_head' => $data['source_head'] ?? null, 'target_head' => $data['target_head'] ?? null]),
        );

        return response()->json($commit->load('author:id,name'), 201);
    }
}
