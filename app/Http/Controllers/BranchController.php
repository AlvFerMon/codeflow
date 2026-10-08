<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Services\VcsService;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request, Repository $repository, VcsService $vcs): JsonResponse
    {
        $this->authorizeRole($request, $repository->project, Roles::OBSERVER);

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
        $project = $repository->project;
        $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:150', 'regex:/^[A-Za-z0-9._\/-]+$/',
                Rule::unique('branches')->where('repository_id', $repository->id),
            ],
            'from_branch_id' => 'nullable|integer',
        ]);

        $from = isset($data['from_branch_id'])
            ? Branch::where('repository_id', $repository->id)->findOrFail($data['from_branch_id'])
            : Branch::where('repository_id', $repository->id)->where('name', $repository->default_branch)->first();

        $branch = Branch::create([
            'repository_id' => $repository->id,
            'name' => $data['name'],
            'head_commit_id' => $from?->head_commit_id,
            'created_by' => $request->user()->id,
        ]);

        Activity::log($project, $request->user(), 'branch.created', $branch, ['name' => $branch->name]);

        return response()->json($branch, 201);
    }

    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        $repository = $branch->repository;
        $this->authorizeRole($request, $repository->project, Roles::COLLABORATOR);

        abort_if($branch->name === $repository->default_branch, 422, 'No se puede eliminar la rama por defecto.');

        $hasOpenPr = PullRequest::where('repository_id', $repository->id)
            ->whereIn('status', PullRequest::OPEN_STATES)
            ->where(fn ($q) => $q->where('source_branch_id', $branch->id)->orWhere('target_branch_id', $branch->id))
            ->exists();
        abort_if($hasOpenPr, 422, 'La rama tiene Pull Requests abiertos.');

        Activity::log($repository->project, $request->user(), 'branch.deleted', null, ['name' => $branch->name]);
        $branch->delete();

        return response()->json(null, 204);
    }
}
