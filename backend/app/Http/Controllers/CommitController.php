<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Commit;
use App\Models\Repository;
use App\Services\RepositoryService;
use App\Services\VcsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommitController extends Controller
{
    /** Historial de una rama (commits alcanzables desde su head). */
    public function branchHistory(Branch $branch, VcsService $vcs)
    {
        Gate::authorize('view', $branch->repository);

        $ids = array_keys($vcs->ancestors($vcs->graph($branch->repository_id), $branch->head_commit_id));

        return Commit::whereIn('id', $ids)->with('author:id,name')->latest('id')->paginate(30);
    }

    /** Todos los commits del repositorio. */
    public function index(Repository $repository)
    {
        Gate::authorize('view', $repository);

        return Commit::where('repository_id', $repository->id)->with('author:id,name')->latest('id')->paginate(30);
    }

    /**
     * Crea un commit en una rama. Sirve para subir archivos, editarlos, borrarlos
     * y crear carpetas (añadiendo un archivo ".gitkeep" dentro de la carpeta).
     */
    public function store(Request $request, Branch $branch, RepositoryService $repositories): JsonResponse
    {
        Gate::authorize('push', $branch->repository);

        $data = $request->validate([
            'message' => 'required|string|max:500',
            'changes' => 'required|array|min:1|max:'.(int) config('codeflow.limits.max_files_per_commit'),
            'changes.*.path' => 'required|string|max:255',
            'changes.*.content' => 'nullable|string|max:'.(int) config('codeflow.limits.max_file_bytes'),
            'changes.*.delete' => 'boolean',
        ]);

        $commit = $repositories->commitChanges($branch, $request->user(), $data['message'], $data['changes']);

        return response()->json($commit->load('author:id,name'), 201);
    }

    public function show(Commit $commit, VcsService $vcs): JsonResponse
    {
        Gate::authorize('view', $commit->repository);

        return response()->json(array_merge($commit->load('author:id,name')->toArray(), [
            'files' => $vcs->diff($commit->parent_id, $commit->id),
        ]));
    }
}
