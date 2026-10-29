<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Commit;
use App\Models\Repository;
use App\Services\RepositoryService;
use App\Services\VcsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class CommitController extends Controller
{
    /** Historial de una rama (commits alcanzables desde su head). */
    public function branchHistory(Branch $branch, VcsService $vcs)
    {
        Gate::authorize('view', $branch->repository);

        // Paginación sobre la lista de ids (ordenada) para no enviar un IN con todo el historial
        $ids = $vcs->historyIds($branch->repository_id, $branch->head_commit_id);
        $page = max(1, (int) request()->query('page', 1));
        $items = Commit::whereIn('id', array_slice($ids, ($page - 1) * 30, 30) ?: [0])
            ->with('author:id,name')->orderByDesc('id')->get();

        return new LengthAwarePaginator($items, count($ids), 30, $page, ['path' => request()->url(), 'query' => request()->query()]);
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

        $maxBytes = (int) config('codeflow.limits.max_file_bytes');
        $data = $request->validate([
            'message' => 'required|string|max:500',
            'changes' => 'required|array|min:1|max:'.(int) config('codeflow.limits.max_files_per_commit'),
            'changes.*.path' => 'required|string|max:255',
            // En base64 el texto ocupa ~4/3 del archivo; el tamaño real se comprueba al guardarlo
            'changes.*.content' => 'nullable|string|max:'.(int) ceil($maxBytes * 4 / 3 + 8),
            'changes.*.encoding' => 'nullable|in:utf8,base64',
            'changes.*.delete' => 'boolean',
            // Commit sobre el que el cliente (CLI) hizo los cambios: si la rama ha avanzado → 409
            'base_commit_id' => 'nullable|integer',
        ]);

        $changes = array_map(function (array $change) {
            if (($change['encoding'] ?? 'utf8') === 'base64' && isset($change['content'])) {
                $decoded = base64_decode($change['content'], true);
                abort_if($decoded === false, 422, "El contenido de {$change['path']} no es base64 válido.");
                $change['content'] = $decoded;
            }
            unset($change['encoding']);

            return $change;
        }, $data['changes']);

        $options = $request->exists('base_commit_id') ? ['expected_head' => $data['base_commit_id'] ?? null] : [];
        $commit = $repositories->commitChanges($branch, $request->user(), $data['message'], $changes, $options);

        return response()->json($commit->load('author:id,name'), 201);
    }

    public function show(Commit $commit, VcsService $vcs): JsonResponse
    {
        Gate::authorize('view', $commit->repository);

        return response()->json(array_merge($commit->load('author:id,name')->toArray(), [
            'files' => $vcs->diff($commit->parent_id, $commit->id),
        ]));
    }

    /** Deshace el commit en la rama indicada (branch_id) creando un commit nuevo. */
    public function revert(Request $request, Commit $commit, RepositoryService $repositories): JsonResponse
    {
        $data = $request->validate(['branch_id' => 'required|integer']);
        $branch = Branch::where('repository_id', $commit->repository_id)->findOrFail($data['branch_id']);
        Gate::authorize('push', $branch->repository);

        $revert = $repositories->revertCommit($branch, $commit, $request->user());

        return response()->json($revert->load('author:id,name'), 201);
    }
}
