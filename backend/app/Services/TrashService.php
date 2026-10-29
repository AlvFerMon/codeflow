<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Repository;
use App\Models\User;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Papelera: borrar un proyecto o repositorio lo oculta durante DAYS días (se puede restaurar).
 * Pasado ese tiempo, o al vaciarlo a mano, se borra de verdad con todo su contenido.
 * Un proyecto en la papelera lleva dentro sus repositorios (con la misma fecha de borrado).
 */
class TrashService
{
    public const DAYS = 30;

    // ── Mover a la papelera ────────────────────────────────────────────────

    public function trashProject(Project $project, User $user): void
    {
        DB::transaction(function () use ($project, $user) {
            $at = now();
            Repository::where('project_id', $project->id)->update(['deleted_at' => $at]);
            $project->forceFill(['deleted_at' => $at])->saveQuietly();
            Activity::log($project, $user, 'project.trashed', $project, ['name' => $project->name]);
        });
    }

    // ── Restaurar ──────────────────────────────────────────────────────────

    public function restoreProject(Project $project, User $user): void
    {
        abort_unless($project->trashed(), 422, 'El proyecto no está en la papelera.');

        DB::transaction(function () use ($project, $user) {
            // Vuelven los repositorios que se borraron junto con el proyecto (no los borrados antes)
            Repository::onlyTrashed()->where('project_id', $project->id)->where('deleted_at', $project->deleted_at)->update(['deleted_at' => null]);
            $project->restore();
            Activity::log($project, $user, 'project.restored', $project, ['name' => $project->name]);
        });
    }

    public function restoreRepository(Repository $repository, User $user): void
    {
        abort_unless($repository->trashed(), 422, 'El repositorio no está en la papelera.');
        abort_if($repository->project?->trashed(), 422, 'Su proyecto está en la papelera: restaura el proyecto.');
        abort_if(
            Repository::where('project_id', $repository->project_id)->where('name', $repository->name)->exists(),
            422, 'Ya hay otro repositorio con ese nombre en el proyecto.'
        );

        $repository->restore();
        Activity::log($repository->project, $user, 'repository.restored', $repository, ['name' => $repository->name]);
    }

    // ── Borrar definitivamente ─────────────────────────────────────────────

    public function destroyProject(Project $project): void
    {
        abort_unless($project->trashed(), 422, 'Primero hay que mover el proyecto a la papelera.');
        $project->forceDelete(); // la base de datos borra en cascada repositorios, issues, PRs, actividad…
    }

    public function destroyRepository(Repository $repository): void
    {
        abort_unless($repository->trashed(), 422, 'Primero hay que mover el repositorio a la papelera.');
        $repository->forceDelete();
    }

    /** Borra lo que lleva más de DAYS días en la papelera. Devuelve cuántos elementos se han borrado. */
    public function purgeExpired(): int
    {
        $limit = now()->subDays(self::DAYS);
        $count = 0;

        Project::onlyTrashed()->where('deleted_at', '<=', $limit)->each(function (Project $p) use (&$count) {
            $p->forceDelete();
            $count++;
        });
        Repository::onlyTrashed()->where('deleted_at', '<=', $limit)->each(function (Repository $r) use (&$count) {
            $r->forceDelete();
            $count++;
        });

        return $count;
    }

    // ── Listado ────────────────────────────────────────────────────────────

    /**
     * Lo que el usuario puede restaurar: sus proyectos en la papelera y los repositorios borrados
     * de proyectos de los que es propietario (si el proyecto sigue activo).
     *
     * @return array{projects: Collection, repositories: Collection}
     */
    public function listFor(User $user): array
    {
        $ownedIds = \App\Models\ProjectMember::where('user_id', $user->id)->where('role', Roles::OWNER)->pluck('project_id');

        $projects = Project::onlyTrashed()->whereIn('id', $ownedIds)
            ->withCount(['repositories' => fn ($q) => $q->withTrashed()])
            ->orderByDesc('deleted_at')->get();

        $repositories = Repository::onlyTrashed()->whereIn('project_id', $ownedIds)
            ->whereHas('project', fn ($q) => $q->whereNull('projects.deleted_at')) // los de proyectos borrados van con su proyecto
            ->with('project:id,name')->orderByDesc('deleted_at')->get();

        $expires = fn ($m) => $m->deleted_at->copy()->addDays(self::DAYS);

        return [
            'projects' => $projects->map(fn (Project $p) => [
                'id' => $p->id, 'name' => $p->name, 'repositories' => $p->repositories_count,
                'deleted_at' => $p->deleted_at, 'expires_at' => $expires($p),
            ]),
            'repositories' => $repositories->map(fn (Repository $r) => [
                'id' => $r->id, 'name' => $r->name, 'project' => ['id' => $r->project->id, 'name' => $r->project->name],
                'deleted_at' => $r->deleted_at, 'expires_at' => $expires($r),
            ]),
        ];
    }
}
