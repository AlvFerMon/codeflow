<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Repository;
use App\Services\IssueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IssueController extends Controller
{
    public function __construct(private IssueService $issues) {}

    public function index(Request $request, Repository $repository)
    {
        Gate::authorize('view', $repository);

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
        Gate::authorize('create', [Issue::class, $repository]);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:20000',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'priority' => ['nullable', Rule::in(Issue::PRIORITIES)],
            'labels' => 'nullable|array|max:20',
            'labels.*' => 'string|max:30',
        ]);

        $issue = $this->issues->create($repository, $request->user(), $data);

        return response()->json($issue->load(['author:id,name', 'assignee:id,name']), 201);
    }

    public function show(Issue $issue): JsonResponse
    {
        Gate::authorize('view', $issue->repository);

        $issue->load([
            'author:id,name',
            'assignee:id,name',
            // Solo los 20 comentarios más recientes; el resto en /comments (paginado)
            'comments' => fn ($q) => $q->with('user:id,name')->latest('id')->limit(20),
            'pullRequests:id,number,title,status',
            'activity' => fn ($q) => $q->with('user:id,name')->latest('id')->limit(50),
        ])->loadCount('comments');

        $payload = $issue->toArray();
        $payload['history'] = $payload['activity'];
        unset($payload['activity']);

        return response()->json($payload);
    }

    public function update(Request $request, Issue $issue): JsonResponse
    {
        Gate::authorize('update', $issue);

        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:20000',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'priority' => ['sometimes', Rule::in(Issue::PRIORITIES)],
            'status' => ['sometimes', Rule::in(Issue::STATUSES)],
            'labels' => 'nullable|array|max:20',
            'labels.*' => 'string|max:30',
        ]);

        $issue = $this->issues->update($issue, $request->user(), $data);

        return response()->json($issue->load(['author:id,name', 'assignee:id,name']));
    }

    public function destroy(Request $request, Issue $issue): JsonResponse
    {
        Gate::authorize('delete', $issue);

        $this->issues->delete($issue, $request->user());

        return response()->json(null, 204);
    }

    public function comments(Issue $issue): JsonResponse
    {
        Gate::authorize('view', $issue->repository);

        // Paginados (30) del más antiguo al más reciente
        return response()->json($issue->comments()->with('user:id,name')->orderBy('id')->paginate(30));
    }

    public function storeComment(Request $request, Issue $issue): JsonResponse
    {
        Gate::authorize('comment', $issue);

        $data = $request->validate(['body' => 'required|string|max:10000']);

        $comment = $this->issues->comment($issue, $request->user(), $data['body']);

        return response()->json($comment->load('user:id,name'), 201);
    }
}
