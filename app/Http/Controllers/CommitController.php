<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Commit;
use App\Models\Repository;
use App\Services\VcsService;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommitController extends Controller
{
    /** Historial de una rama (commits alcanzables desde su head). */
    public function branchHistory(Request $request, Branch $branch, VcsService $vcs)
    {
        $this->authorizeRole($request, $branch->repository->project, Roles::OBSERVER);

        $ids = array_keys($vcs->ancestors($vcs->graph($branch->repository_id), $branch->head_commit_id));

        return Commit::whereIn('id', $ids)->with('author:id,name')->latest('id')->paginate(30);
    }

    /** Todos los commits del repositorio. */
    public function index(Request $request, Repository $repository)
    {
        $this->authorizeRole($request, $repository->project, Roles::OBSERVER);

        return Commit::where('repository_id', $repository->id)->with('author:id,name')->latest('id')->paginate(30);
    }

    /**
     * Crea un commit en una rama. Sirve para subir archivos, editarlos, borrarlos
     * y crear carpetas (añadiendo un archivo ".gitkeep" dentro de la carpeta).
     */
    public function store(Request $request, Branch $branch, VcsService $vcs): JsonResponse
    {
        $project = $branch->repository->project;
        $this->authorizeRole($request, $project, Roles::COLLABORATOR);

        $data = $request->validate([
            'message' => 'required|string|max:500',
            'changes' => 'required|array|min:1|max:200',
            'changes.*.path' => 'required|string|max:255',
            'changes.*.content' => 'nullable|string|max:1048576',
            'changes.*.delete' => 'boolean',
        ]);

        $commit = $vcs->commit($branch, $request->user(), $data['message'], $data['changes']);

        Activity::log($project, $request->user(), 'commit.created', $commit, [
            'branch' => $branch->name,
            'sha' => substr($commit->sha, 0, 7),
            'message' => $commit->message,
        ]);

        return response()->json($commit->load('author:id,name'), 201);
    }

    public function show(Request $request, Commit $commit, VcsService $vcs): JsonResponse
    {
        $this->authorizeRole($request, $commit->repository->project, Roles::OBSERVER);

        return response()->json(array_merge($commit->load('author:id,name')->toArray(), [
            'files' => $vcs->diff($commit->parent_id, $commit->id),
        ]));
    }
}
