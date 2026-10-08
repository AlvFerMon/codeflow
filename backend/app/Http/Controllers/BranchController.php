<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Services\RepositoryService;
use App\Services\VcsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function __construct(private RepositoryService $repositories) {}

    public function index(Repository $repository, VcsService $vcs): JsonResponse
    {
        Gate::authorize('view', $repository);

        $graph = $vcs->graph($repository->id);

        $branches = $repository->branches()->with(['headCommit.author:id,name', 'creator:id,name'])->get()
            ->map(function (Branch $branch) use ($graph, $vcs, $repository) {
                return array_merge($branch->toArray(), [
                    'is_default' => $branch->name === $repository->default_branch,
                    'commits_count' => count($vcs->ancestors($graph, $branch->head_commit_id)),
                    'last_modified' => ($branch->headCommit?->created_at ?? $branch->created_at),
                    'open_pull_requests' => PullRequest::where('repository_id', $repository->id)
                        ->whereIn('status', PullRequest::OPEN_STATES)
                        ->where(fn ($q) => $q->where('source_branch_id', $branch->id)->orWhere('target_branch_id', $branch->id))
                        ->count(),
                ]);
            });

        return response()->json($branches->values());
    }

    public function store(Request $request, Repository $repository): JsonResponse
    {
        Gate::authorize('push', $repository);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:150', 'regex:/^[A-Za-z0-9._\/-]+$/',
                Rule::unique('branches')->where('repository_id', $repository->id),
            ],
            'from_branch_id' => 'nullable|integer',
        ]);

        $branch = $this->repositories->createBranch(
            $repository,
            $request->user(),
            $data['name'],
            isset($data['from_branch_id']) ? (int) $data['from_branch_id'] : null,
        );

        return response()->json($branch, 201);
    }

    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        Gate::authorize('push', $branch->repository);

        $this->repositories->deleteBranch($branch, $request->user());

        return response()->json(null, 204);
    }
}
