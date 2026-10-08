<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Account\Presenters\ActivityPresenter;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\ProjectService;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projects) {}

    /** Mis proyectos (el administrador ve todos). */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Project::class);

        $user = $request->user();
        $roles = ProjectMember::where('user_id', $user->id)->pluck('role', 'project_id');

        $projects = Project::query()
            ->with('owner:id,name')
            ->withCount('repositories')
            ->when(! $user->isAdmin(), fn ($q) => $q->whereIn('id', $roles->keys()))
            ->latest()
            ->paginate(18);

        $projects->getCollection()->each(fn (Project $project) => $project->setAttribute('my_role', $roles[$project->id] ?? null));

        return view('projects.index', ['projects' => $projects, 'isAdmin' => $user->isAdmin()]);
    }

    public function create(): View
    {
        Gate::authorize('create', Project::class);

        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Project::class);

        $project = $this->projects->create($request->user(), $this->validated($request));

        return redirect()->route('projects.show', $project)->with('status', 'Proyecto creado. Ya puedes añadir repositorios y miembros.');
    }

    public function show(Request $request, Project $project, ActivityPresenter $presenter): View
    {
        Gate::authorize('view', $project);

        $user = $request->user();

        $activity = ActivityPresenter::eagerLoad(ActivityLog::query())
            ->where('project_id', $project->id)
            ->latest('id')
            ->limit(8)
            ->get();

        return view('projects.show', [
            'project' => $project->load('owner:id,name'),
            // El administrador que no es miembro actúa como propietario (igual que la API)
            'role' => $user->roleIn($project) ?? ($user->isAdmin() ? Roles::OWNER : null),
            'repositories' => $project->repositories()->orderBy('name')->get(),
            'members' => ProjectMember::where('project_id', $project->id)->with('user:id,name,email')->get()
                ->sortBy([fn ($a, $b) => Roles::level($b->role) <=> Roles::level($a->role), fn ($a, $b) => strcasecmp($a->user?->name ?? '', $b->user?->name ?? '')]),
            'activity' => $presenter->presentMany($activity),
        ]);
    }

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('projects.edit', ['project' => $project]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->projects->update($project, $request->user(), $this->validated($request));

        return redirect()->route('projects.show', $project)->with('status', 'Proyecto actualizado.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $name = $project->name;
        $this->projects->delete($project, $request->user());

        return redirect()->route('projects.index')->with('status', "Se ha eliminado el proyecto «{$name}».");
    }

    /** @return array{name:string,description:?string} */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
        ], [
            'name.required' => 'El nombre del proyecto es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'description.max' => 'La descripción no puede superar los 2000 caracteres.',
        ]);
    }
}
