<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Repository;
use App\Services\TrashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Papelera (API /api/trash… y web /papelera…). Solo el propietario puede restaurar o vaciar. */
class TrashController extends Controller
{
    public function __construct(private TrashService $trash) {}

    public function index(Request $request)
    {
        $this->trash->purgeExpired();
        $items = $this->trash->listFor($request->user());

        return $request->expectsJson()
            ? response()->json(['days' => TrashService::DAYS, ...$items])
            : view('trash.index', ['days' => TrashService::DAYS, ...$items]);
    }

    public function restoreProject(Request $request, int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $project);
        $this->trash->restoreProject($project, $request->user());

        return $this->done($request, "Proyecto «{$project->name}» restaurado.", route('projects.show', $project));
    }

    public function destroyProject(Request $request, int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $project);
        $this->trash->destroyProject($project);

        return $this->done($request, "Proyecto «{$project->name}» borrado definitivamente.");
    }

    public function restoreRepository(Request $request, int $id)
    {
        $repository = Repository::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $repository);
        $this->trash->restoreRepository($repository, $request->user());

        return $this->done($request, "Repositorio «{$repository->name}» restaurado.", route('repositories.show', $repository));
    }

    public function destroyRepository(Request $request, int $id)
    {
        $repository = Repository::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $repository);
        $this->trash->destroyRepository($repository);

        return $this->done($request, "Repositorio «{$repository->name}» borrado definitivamente.");
    }

    private function done(Request $request, string $message, ?string $to = null)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return ($to ? redirect($to) : redirect()->route('trash.index'))->with('status', $message);
    }
}
