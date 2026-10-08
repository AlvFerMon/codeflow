<?php

namespace App\Http\Controllers\Web\Code;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Code\Concerns\InteractsWithCode;
use App\Models\Commit;
use App\Models\Repository;
use App\Services\VcsService;
use Illuminate\Http\Request;
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

        $commits = Commit::whereIn('id', $ids ?: [0])
            ->with('author:id,name')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

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
    public function show(Repository $repository, string $sha): View
    {
        Gate::authorize('view', $repository);

        abort_unless(preg_match('/^[0-9a-f]{7,64}$/i', $sha), 404);

        $matches = Commit::where('repository_id', $repository->id)
            ->where('sha', 'like', strtolower($sha).'%')
            ->limit(2)
            ->get();
        abort_unless($matches->count() === 1, 404, 'Commit no encontrado.');

        $commit = $matches->first()->load('author:id,name');
        $parents = Commit::whereIn('id', array_filter([$commit->parent_id, $commit->second_parent_id]))
            ->get(['id', 'repository_id', 'sha'])
            ->sortBy(fn (Commit $p) => $p->id === $commit->parent_id ? 0 : 1)
            ->values();

        $branchesAtCommit = $repository->branches()->where('head_commit_id', $commit->id)->orderBy('name')->pluck('name');

        return view('commits.show', [
            'repository' => $repository,
            'commit' => $commit,
            'parents' => $parents,
            'branchesAtCommit' => $branchesAtCommit,
            'files' => $this->vcs->diff($commit->parent_id, $commit->id, true),
        ]);
    }
}
