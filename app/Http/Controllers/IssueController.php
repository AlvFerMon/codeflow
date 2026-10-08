<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Repository;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IssueController extends Controller
{
    public function index(Request $request, Repository $repository)
    {
        $this->authorizeRole($request, $repository->project, Roles::OBSERVER);

        return Issue::where('repository_id', $repository->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('priority'), fn ($q, $v) => $q->where('priority', $v))
            ->when($request->query('assignee_id'), fn ($q, $v) => $q->where('assignee_id', $v))
            ->when($request->query('label'), fn ($q, $v) => $q->whereJsonContains('labels', $v))
            ->when($request->query('q'), fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->with(['author:id,name', 'assignee:id,name'])
            ->withCount('comments')
            ->orderByDesc('number')
            ->paginate(30);
    }

    public function store(Request $request, Repository $repository): JsonResponse
    {
        $project = $repository->project;
        $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:20000',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'priority' => ['nullable', Rule::in(Issue::PRIORITIES)],
            'labels' => 'nullable|array|max:20',
            'labels.*' => 'string|max:30',
        ]);

        if (! empty($data['assignee_id'])) {
            $this->ensureMember($project, $data['assignee_id']);
        }

        $user = $request->user();

        $issue = DB::transaction(function () use ($repository, $data, $user, $project) {
            $number = (int) Issue::where('repository_id', $repository->id)->lockForUpdate()->max('number') + 1;

            $issue = Issue::create([
                'repository_id' => $repository->id,
                'number' => $number,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'author_id' => $user->id,
                'assignee_id' => $data['assignee_id'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'open',
                'labels' => $data['labels'] ?? [],
            ]);

            Activity::log($project, $user, 'issue.created', $issue, ['number' => $issue->number, 'title' => $issue->title]);

            return $issue;
        });

        return response()->json($issue->load(['author:id,name', 'assignee:id,name']), 201);
    }

    public function show(Request $request, Issue $issue): JsonResponse
    {
        $this->authorizeRole($request, $issue->repository->project, Roles::OBSERVER);

        $issue->load([
            'author:id,name',
            'assignee:id,name',
            'comments.user:id,name',
            'pullRequests:id,number,title,status',
            'activity.user:id,name',
        ]);

        $payload = $issue->toArray();
        $payload['history'] = $payload['activity'];
        unset($payload['activity']);

        return response()->json($payload);
    }

    public function update(Request $request, Issue $issue): JsonResponse
    {
        $project = $issue->repository->project;
        $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:20000',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'priority' => ['sometimes', Rule::in(Issue::PRIORITIES)],
            'status' => ['sometimes', Rule::in(Issue::STATUSES)],
            'labels' => 'nullable|array|max:20',
            'labels.*' => 'string|max:30',
        ]);

        if (! empty($data['assignee_id'])) {
            $this->ensureMember($project, $data['assignee_id']);
        }

        $before = $issue->only(array_keys($data));
        $issue->fill($data);
        $dirty = array_keys($issue->getDirty());
        $issue->save();

        if ($dirty) {
            $changes = [];
            foreach ($dirty as $field) {
                $changes[$field] = ['from' => $before[$field] ?? null, 'to' => $issue->{$field}];
            }
            Activity::log($project, $request->user(), 'issue.updated', $issue, ['changes' => $changes]);
        }

        return response()->json($issue->load(['author:id,name', 'assignee:id,name']));
    }

    public function storeComment(Request $request, Issue $issue): JsonResponse
    {
        $project = $issue->repository->project;
        $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        $data = $request->validate(['body' => 'required|string|max:10000']);

        $comment = IssueComment::create(['issue_id' => $issue->id, 'user_id' => $request->user()->id, 'body' => $data['body']]);
        Activity::log($project, $request->user(), 'issue.commented', $issue, ['number' => $issue->number]);

        return response()->json($comment->load('user:id,name'), 201);
    }
}
