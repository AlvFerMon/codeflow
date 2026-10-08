<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Issue;
use App\Models\Project;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Services\VcsService;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RepositoryController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorizeRole($request, $project, Roles::OBSERVER);

        return response()->json($project->repositories()->orderBy('name')->get());
    }

    public function store(Request $request, Project $project, VcsService $vcs): JsonResponse
    {
        $this->authorizeRole($request, $project, Roles::OWNER);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('repositories')->where('project_id', $project->id),
            ],
            'description' => 'nullable|string|max:2000',
            'init_readme' => 'boolean',
        ]);

        $user = $request->user();

        $repository = DB::transaction(function () use ($data, $project, $user, $vcs, $request) {
            $repository = Repository::create([
                'project_id' => $project->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'default_branch' => 'main',
            ]);

            $branch = Branch::create(['repository_id' => $repository->id, 'name' => 'main', 'created_by' => $user->id]);

            if ($request->boolean('init_readme', true)) {
                $vcs->commit($branch, $user, 'Initial commit', [
                    ['path' => 'README.md', 'content' => "# {$repository->name}\n"],
                ]);
            }

            Activity::log($project, $user, 'repository.created', $repository, ['name' => $repository->name]);

            return $repository;
        });

        return response()->json($repository, 201);
    }

    public function show(Request $request, Repository $repository): JsonResponse
    {
        $this->authorizeRole($request, $repository->project, Roles::OBSERVER);

        return response()->json(array_merge($repository->toArray(), [
            'branches_count' => $repository->branches()->count(),
            'commits_count' => $repository->commits()->count(),
            'open_issues' => Issue::where('repository_id', $repository->id)->whereIn('status', ['open', 'in_progress'])->count(),
            'open_pull_requests' => PullRequest::where('repository_id', $repository->id)->whereIn('status', PullRequest::OPEN_STATES)->count(),
        ]));
    }

    public function update(Request $request, Repository $repository): JsonResponse
    {
        $this->authorizeRole($request, $repository->project, Roles::OWNER);

        $data = $request->validate([
            'name' => [
                'sometimes', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('repositories')->where('project_id', $repository->project_id)->ignore($repository->id),
            ],
            'description' => 'nullable|string|max:2000',
        ]);

        $repository->update($data);

        return response()->json($repository);
    }

    public function destroy(Request $request, Repository $repository): JsonResponse
    {
        $this->authorizeRole($request, $repository->project, Roles::OWNER);

        Activity::log($repository->project, $request->user(), 'repository.deleted', null, ['name' => $repository->name]);
        $repository->delete();

        return response()->json(null, 204);
    }
}
