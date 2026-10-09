<?php

namespace App\Http\Controllers\Web\Code;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Code\Concerns\InteractsWithCode;
use App\Models\Branch;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Services\RepositoryService;
use App\Services\VcsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Ramas de un repositorio: listado, creación y borrado. */
class BranchController extends Controller
{
    use InteractsWithCode;

    public function __construct(private RepositoryService $repositories) {}

    public function index(Repository $repository, VcsService $vcs): View
    {
        Gate::authorize('view', $repository);

        $graph = $vcs->graph($repository->id);

        // Página de 20 ramas: la principal primero y después por fecha de su último commit (en SQL)
        $branches = $repository->branches()
            ->select('branches.*')
            ->leftJoin('commits as head', 'head.id', '=', 'branches.head_commit_id')
            ->orderByRaw('CASE WHEN branches.name = ? THEN 0 ELSE 1 END', [$repository->default_branch])
            ->orderByRaw('COALESCE(head.created_at, branches.created_at) DESC')
            ->with(['headCommit.author:id,name', 'creator:id,name'])
            ->paginate(20);

        $defaultHead = $repository->branches()->where('name', $repository->default_branch)->first(['id', 'head_commit_id']);
        $defaultAncestors = $vcs->ancestors($graph, $defaultHead?->head_commit_id);

        $openPrs = PullRequest::where('repository_id', $repository->id)
            ->whereIn('status', PullRequest::OPEN_STATES)
            ->get(['source_branch_id', 'target_branch_id']);

        $rows = $branches->getCollection()->map(function (Branch $branch) use ($graph, $vcs, $repository, $defaultAncestors, $openPrs) {
            $ancestors = $vcs->ancestors($graph, $branch->head_commit_id);

            return [
                'branch' => $branch,
                'is_default' => $branch->name === $repository->default_branch,
                'commits_count' => count($ancestors),
                'ahead' => count(array_diff_key($ancestors, $defaultAncestors)),
                'behind' => count(array_diff_key($defaultAncestors, $ancestors)),
                'open_prs' => $openPrs->filter(fn ($pr) => in_array($branch->id, [$pr->source_branch_id, $pr->target_branch_id]))->count(),
                'last_modified' => $branch->headCommit?->created_at ?? $branch->created_at,
            ];
        });

        return view('branches.index', [
            'repository' => $repository,
            'rows' => $rows,
            'branches' => $branches,
            'fromOptions' => $repository->branches()->orderBy('name')->pluck('name', 'id')->all(),
            'defaultId' => $defaultHead?->id,
        ]);
    }

    public function store(Request $request, Repository $repository): RedirectResponse
    {
        Gate::authorize('push', $repository);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._\/-]+$/',
                // Reglas de nombres del motor (ARQUITECTURA §9.5): sin "//", "..", ni empezar o acabar en "/" o "."
                'not_regex:/(^[\/.])|([\/.]$)|\/\/|\.\./',
                Rule::unique('branches')->where('repository_id', $repository->id),
            ],
            'from_branch_id' => [
                'nullable', 'integer',
                Rule::exists('branches', 'id')->where('repository_id', $repository->id),
            ],
        ], [
            'name.required' => 'Escribe el nombre de la rama.',
            'name.max' => 'El nombre de la rama no puede superar los 100 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras, números, ".", "_", "-" y "/".',
            'name.not_regex' => 'El nombre no puede empezar ni acabar en "/" o ".", ni contener "//" o "..".',
            'name.unique' => 'Ya existe una rama con ese nombre.',
            'from_branch_id.exists' => 'La rama de origen no existe.',
        ]);

        $branch = $this->attempt(fn () => $this->repositories->createBranch(
            $repository,
            $request->user(),
            $data['name'],
            isset($data['from_branch_id']) ? (int) $data['from_branch_id'] : null,
        ));
        if ($this->failed($branch)) {
            return $branch;
        }

        return redirect()->route('branches.index', $repository)->with('status', "Rama {$branch->name} creada.");
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        $repository = $branch->repository;
        Gate::authorize('push', $repository);

        $back = route('branches.index', $repository);
        $name = $branch->name;

        $result = $this->attempt(function () use ($branch, $request) {
            $this->repositories->deleteBranch($branch, $request->user());

            return true;
        }, $back);
        if ($this->failed($result)) {
            return $result;
        }

        return redirect($back)->with('status', "Rama {$name} eliminada.");
    }
}
