<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Project;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Services\RepositoryService;
use App\Support\Visibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RepositoryController extends Controller
{
    public function __construct(private RepositoryService $repositories) {}

    /** Repositorios públicos de toda la plataforma (sin login). Búsqueda con ?q= */
    public function explore(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100']);

        return Repository::query()
            ->where('visibility', Visibility::PUBLIC)
            ->when($request->query('q'), fn ($q, $term) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")
            ))
            ->with('project:id,name')
            ->latest('updated_at')
            ->paginate(20);
    }

    /** Miembros: todos los repositorios del proyecto. Resto: solo los públicos (404 si no hay ninguno). */
    /** Repositorios del proyecto, paginados (20). Quien no es miembro solo ve los públicos. */
    public function index(Project $project): JsonResponse
    {
        $query = Repository::query()->forListing()->where('project_id', $project->id)->orderBy('name');

        if (Gate::denies('view', $project)) {
            $query->where('visibility', Visibility::PUBLIC);
            $page = $query->paginate(20);
            abort_if($page->total() === 0, 404);

            return response()->json($page);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('create', [Repository::class, $project]);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('repositories')->where('project_id', $project->id),
            ],
            'description' => 'nullable|string|max:2000',
            'visibility' => ['nullable', Rule::in(Visibility::ALL)],
            'init_readme' => 'boolean',
        ]);

        $data['init_readme'] = $request->boolean('init_readme', true);
        $data = array_filter($data, fn ($v) => $v !== null);

        return response()->json($this->repositories->create($project, $request->user(), $data), 201);
    }

    public function show(Repository $repository): JsonResponse
    {
        Gate::authorize('view', $repository);

        return response()->json(array_merge($repository->load('project:id,name')->toArray(), [
            'branches_count' => $repository->branches()->count(),
            'commits_count' => $repository->commits()->count(),
            'open_issues' => Issue::where('repository_id', $repository->id)->whereIn('status', ['open', 'in_progress'])->count(),
            'open_pull_requests' => PullRequest::where('repository_id', $repository->id)->whereIn('status', PullRequest::OPEN_STATES)->count(),
            // Permisos del usuario actual (todo false para invitados), útil para los clientes
            'can' => [
                'update' => Gate::allows('update', $repository),
                'delete' => Gate::allows('delete', $repository),
                'push' => Gate::allows('push', $repository),
                'create_issue' => Gate::allows('create', [Issue::class, $repository]),
                'create_pull_request' => Gate::allows('create', [PullRequest::class, $repository]),
            ],
        ]));
    }

    public function update(Request $request, Repository $repository): JsonResponse
    {
        Gate::authorize('update', $repository);

        $data = $request->validate([
            'name' => [
                'sometimes', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('repositories')->where('project_id', $repository->project_id)->ignore($repository->id),
            ],
            'description' => 'nullable|string|max:2000',
            'visibility' => ['sometimes', Rule::in(Visibility::ALL)],
        ]);

        return response()->json($this->repositories->update($repository, $request->user(), $data));
    }

    public function destroy(Request $request, Repository $repository): JsonResponse
    {
        Gate::authorize('delete', $repository);

        $this->repositories->delete($repository, $request->user());

        return response()->json(null, 204);
    }
}
