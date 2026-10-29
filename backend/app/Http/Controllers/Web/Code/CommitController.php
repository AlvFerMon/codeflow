<?php

namespace App\Http\Controllers\Web\Code;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Code\Concerns\InteractsWithCode;
use App\Models\Commit;
use App\Support\DiffSelection;
use App\Models\Repository;
use App\Services\VcsService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Historial de commits y detalle de un commit con su diff. */
class CommitController extends Controller
{
    use InteractsWithCode;

    public function __construct(private VcsService $vcs) {}

    /** Historial de la rama ?branch= (y opcionalmente solo de un archivo ?path=). */
    public function index(Request $request, Repository $repository): View
    {
        Gate::authorize('view', $repository);

        $branch = $this->resolveBranch($repository, $request->query('branch'));
        $path = $this->cleanPath($request->query('path'));

        $ids = $this->vcs->historyIds($repository->id, $branch?->head_commit_id, $path !== '' ? $path : null);

        // Se pagina la lista de ids en PHP (ya ordenada) y solo se consultan los 30 de la página
        $perPage = 30;
        $page = max(1, (int) $request->query('page', 1));
        $pageIds = array_slice($ids, ($page - 1) * $perPage, $perPage);
        $items = $pageIds
            ? Commit::whereIn('id', $pageIds)
                ->select(['id', 'repository_id', 'parent_id', 'second_parent_id', 'author_id', 'sha', 'message', 'created_at'])
                ->with('author:id,name')
                ->orderByDesc('id')
                ->get()
            : collect();
        $commits = (new LengthAwarePaginator($items, count($ids), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]));

        return view('commits.index', [
            'repository' => $repository,
            'branch' => $branch,
            'branches' => $repository->branches()->orderBy('name')->pluck('name'),
            'path' => $path,
            'commits' => $commits,
            'days' => $commits->getCollection()->groupBy(fn (Commit $c) => $c->created_at->format('Y-m-d')),
        ]);
    }

    /** Detalle: {sha} completo o prefijo único de al menos 7 caracteres. */
    /** Busca un commit por su sha completo o abreviado (mínimo 7 caracteres). */
    private function findCommit(Repository $repository, string $sha): Commit
    {
        abort_unless(preg_match('/^[0-9a-f]{7,64}$/i', $sha), 404);

        $matches = Commit::where('repository_id', $repository->id)
            ->where('sha', 'like', strtolower($sha).'%')
            ->limit(2)
            ->get();
        abort_unless($matches->count() === 1, 404, 'Commit no encontrado.');

        return $matches->first();
    }

    /** Fragmento HTML con el diff de un archivo del commit (se carga al desplegarlo). */
    public function diff(Request $request, Repository $repository, string $sha): View
    {
        Gate::authorize('view', $repository);

        $commit = $this->findCommit($repository, $sha);
        $path = (string) $request->query('path', '');
        $file = $path !== '' ? $this->vcs->fileDiff($commit->parent_id, $commit->id, $path) : null;
        abort_if($file === null, 404);

        return view('partials._diff-fragment', [
            'file' => $file,
            'full' => $request->boolean('full'),
            'fullUrl' => route('commits.diff', [$repository, $commit->sha, 'path' => $path, 'full' => 1]),
        ]);
    }

    public function show(Repository $repository, string $sha): View
    {
        Gate::authorize('view', $repository);

        $commit = $this->findCommit($repository, $sha)->load('author:id,name');
        $parents = Commit::whereIn('id', array_filter([$commit->parent_id, $commit->second_parent_id]))
            ->get(['id', 'repository_id', 'sha'])
            ->sortBy(fn (Commit $p) => $p->id === $commit->parent_id ? 0 : 1)
            ->values();

        $branchesAtCommit = $repository->branches()->where('head_commit_id', $commit->id)->orderBy('name')->pluck('name');
        // Ramas donde se puede deshacer (las que contienen el commit), solo para quien puede subir cambios
        $revertBranches = Gate::allows('push', $repository) ? $this->vcs->branchesContaining($commit)->pluck('name') : collect();

        return view('commits.show', [
            'repository' => $repository,
            'commit' => $commit,
            'parents' => $parents,
            'branchesAtCommit' => $branchesAtCommit,
            'revertBranches' => $revertBranches,
            'files' => DiffSelection::merge(
                $summary = $this->vcs->diff($commit->parent_id, $commit->id),
                $this->vcs->diff($commit->parent_id, $commit->id, true, DiffSelection::eagerPaths($summary)),
            ),
        ]);
    }

    /** Deshace el commit en una rama (formulario de la página del commit). */
    public function revert(Request $request, Repository $repository, string $sha, \App\Services\RepositoryService $repositories)
    {
        Gate::authorize('push', $repository);
        $commit = $this->findCommit($repository, $sha);
        $branch = $repository->branches()->where('name', (string) $request->input('branch'))->firstOrFail();

        try {
            $revert = $repositories->revertCommit($branch, $commit, $request->user());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('commits.show', [$repository, $revert->sha])
            ->with('status', 'Hecho: este commit deshace '.substr($commit->sha, 0, 7)." en la rama {$branch->name}.");
    }
}
