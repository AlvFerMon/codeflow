<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Commit;
use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Services\ProjectService;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projects) {}

    /** Mis proyectos (el administrador ve todos). */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Project::class);

        $user = $request->user();
        $query = Project::query()->with('owner:id,name');

        if (! $user->isAdmin()) {
            $query->whereIn('id', ProjectMember::where('user_id', $user->id)->select('project_id'));
        }

        return $query->latest()->paginate(20);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Project::class);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
        ]);

        return response()->json($this->projects->create($request->user(), $data), 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $user = $request->user();

        return response()->json(array_merge($project->load('owner:id,name')->toArray(), [
            // El administrador que no es miembro actúa como propietario
            'role' => $user->roleIn($project) ?? ($user->isAdmin() ? Roles::OWNER : null),
            'can' => [
                'update' => $user->can('update', $project),
                'delete' => $user->can('delete', $project),
                'manage_members' => $user->can('manageMembers', $project),
                'create_repository' => $user->can('create', [Repository::class, $project]),
            ],
        ]));
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:2000',
            'settings' => 'nullable|array',
        ]);

        return response()->json($this->projects->update($project, $request->user(), $data));
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $this->projects->delete($project, $request->user());

        return response()->json(null, 204);
    }

    public function dashboard(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

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
