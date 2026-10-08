<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Commit;
use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\PullRequest;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Project::query()->with('owner:id,name');

        if (! $user->isAdmin()) {
            $query->whereIn('id', ProjectMember::where('user_id', $user->id)->select('project_id'));
        }

        return $query->latest()->paginate(20);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        $project = DB::transaction(function () use ($data, $user) {
            $project = Project::create($data + ['owner_id' => $user->id]);
            ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id, 'role' => Roles::OWNER]);
            Activity::log($project, $user, 'project.created', $project);

            return $project;
        });

        return response()->json($project, 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $role = $this->authorizeRole($request, $project, Roles::OBSERVER);

        return response()->json(array_merge(
            $project->load('owner:id,name')->toArray(),
            ['role' => $role]
        ));
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeRole($request, $project, Roles::OWNER);

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:2000',
            'settings' => 'nullable|array',
        ]);

        $project->update($data);

        return response()->json($project);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeRole($request, $project, Roles::OWNER);

        $project->delete();

        return response()->json(null, 204);
    }

    public function dashboard(Request $request, Project $project): JsonResponse
    {
        $this->authorizeRole($request, $project, Roles::OBSERVER);

        $repoIds = $project->repositories()->pluck('id');

        return response()->json([
            'contributors' => ProjectMember::where('project_id', $project->id)->count(),
            'repositories' => $repoIds->count(),
            'branches' => Branch::whereIn('repository_id', $repoIds)->count(),
            'open_pull_requests' => PullRequest::whereIn('repository_id', $repoIds)->whereIn('status', PullRequest::OPEN_STATES)->count(),
            'open_issues' => Issue::whereIn('repository_id', $repoIds)->whereIn('status', ['open', 'in_progress'])->count(),
            'commits' => Commit::whereIn('repository_id', $repoIds)->count(),
        ]);
    }
}
