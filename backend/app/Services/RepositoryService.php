<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Commit;
use App\Models\Project;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\User;
use App\Support\Activity;
use App\Support\Visibility;
use Illuminate\Support\Facades\DB;

class RepositoryService
{
    public function __construct(
        private VcsService $vcs,
        private NotificationService $notifications,
    ) {}

    /**
     * Crea el repositorio con su rama principal y, por defecto, un commit inicial con README.
     *
     * @param  array{name:string,description?:?string,visibility?:string,init_readme?:bool}  $data
     */
    public function create(Project $project, User $user, array $data): Repository
    {
        $repository = DB::transaction(function () use ($project, $user, $data) {
            $attributes = [
                'project_id' => $project->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'default_branch' => 'main',
            ];

            // Si no se indica, se aplica el valor por defecto de la columna (privado)
            if (isset($data['visibility'])) {
                abort_unless(in_array($data['visibility'], Visibility::ALL, true), 422, 'Visibilidad no válida.');
                $attributes['visibility'] = $data['visibility'];
            }

            $repository = Repository::create($attributes);

            $branch = Branch::create(['repository_id' => $repository->id, 'name' => 'main', 'created_by' => $user->id]);

            if ($data['init_readme'] ?? true) {
                $this->vcs->commit($branch, $user, 'Initial commit', [
                    ['path' => 'README.md', 'content' => "# {$repository->name}\n"],
                ]);
            }

            Activity::log($project, $user, 'repository.created', $repository, ['name' => $repository->name]);

            return $repository;
        });

        $this->notifications->notifyProject(
            $project, $user, 'repository.created',
            "{$user->name} creó el repositorio {$repository->name} en el proyecto {$project->name}",
            $repository, repository: $repository,
        );

        return $repository->refresh();
    }

    /** Edita nombre, descripción y visibilidad. */
    public function update(Repository $repository, User $user, array $data): Repository
    {
        $data = array_intersect_key($data, array_flip(['name', 'description', 'visibility']));

        if (isset($data['visibility'])) {
            abort_unless(in_array($data['visibility'], Visibility::ALL, true), 422, 'Visibilidad no válida.');
        }

        $before = $repository->only(array_keys($data));
        $repository->fill($data);
        $dirty = array_keys($repository->getDirty());
        $repository->save();

        if ($dirty) {
            $changes = [];
            foreach ($dirty as $field) {
                $changes[$field] = ['from' => $before[$field] ?? null, 'to' => $repository->{$field}];
            }
            Activity::log($repository->project, $user, 'repository.updated', $repository, ['changes' => $changes]);
        }

        return $repository;
    }

    /** Cambia la rama por defecto (debe existir en el repositorio). */
    public function changeDefaultBranch(Repository $repository, User $user, string $branchName): Repository
    {
        if ($branchName === $repository->default_branch) {
            return $repository;
        }

        abort_unless(
            Branch::where('repository_id', $repository->id)->where('name', $branchName)->exists(),
            422,
            'La rama indicada no existe en el repositorio.'
        );

        $before = $repository->default_branch;
        $repository->update(['default_branch' => $branchName]);

        Activity::log($repository->project, $user, 'repository.updated', $repository, [
            'changes' => ['default_branch' => ['from' => $before, 'to' => $branchName]],
        ]);

        return $repository;
    }

    public function delete(Repository $repository, User $user): void
    {
        Activity::log($repository->project, $user, 'repository.deleted', null, ['name' => $repository->name]);
        $repository->delete();
    }

    /** Crea una rama a partir de otra (por defecto, la rama principal). */
    public function createBranch(Repository $repository, User $user, string $name, ?int $fromBranchId): Branch
    {
        abort_if(
            Branch::where('repository_id', $repository->id)->where('name', $name)->exists(),
            422,
            'Ya existe una rama con ese nombre.'
        );

        $from = $fromBranchId !== null
            ? Branch::where('repository_id', $repository->id)->findOrFail($fromBranchId)
            : Branch::where('repository_id', $repository->id)->where('name', $repository->default_branch)->first();

        $branch = Branch::create([
            'repository_id' => $repository->id,
            'name' => $name,
            'head_commit_id' => $from?->head_commit_id,
            'created_by' => $user->id,
        ]);

        $project = $repository->project;
        Activity::log($project, $user, 'branch.created', $branch, ['name' => $branch->name, 'from' => $from?->name]);

        $this->notifications->notifyProject(
            $project, $user, 'branch.created',
            "{$user->name} creó la rama {$branch->name} en el repositorio {$repository->name}",
            $branch, repository: $repository,
        );

        return $branch;
    }

    /** Borra una rama. Nunca la rama por defecto ni una con Pull Requests abiertos. */
    public function deleteBranch(Branch $branch, User $user): void
    {
        $repository = $branch->repository;

        abort_if($branch->name === $repository->default_branch, 422, 'No se puede eliminar la rama por defecto.');

        $hasOpenPr = PullRequest::where('repository_id', $repository->id)
            ->whereIn('status', PullRequest::OPEN_STATES)
            ->where(fn ($q) => $q->where('source_branch_id', $branch->id)->orWhere('target_branch_id', $branch->id))
            ->exists();
        abort_if($hasOpenPr, 422, 'La rama tiene Pull Requests abiertos.');

        Activity::log($repository->project, $user, 'branch.deleted', null, ['name' => $branch->name, 'repository_id' => $repository->id]);
        $branch->delete();
    }

    /**
     * Crea un commit en la rama: subir, editar o borrar archivos y crear carpetas (con ".gitkeep").
     *
     * @param  array<int,array{path:string,content?:?string,delete?:bool}>  $changes
     */
    public function commitChanges(Branch $branch, User $user, string $message, array $changes): Commit
    {
        $repository = $branch->repository;
        $project = $repository->project;

        $commit = $this->vcs->commit($branch, $user, $message, $changes);

        // El mensaje del commit se guarda cifrado: en la actividad solo rama y sha corto
        $sha = substr($commit->sha, 0, 7);
        Activity::log($project, $user, 'commit.created', $commit, ['branch' => $branch->name, 'sha' => $sha]);

        $this->notifications->notifyProject(
            $project, $user, 'commit.created',
            "{$user->name} subió cambios a la rama {$branch->name} en el repositorio {$repository->name} ({$sha})",
            $commit, repository: $repository,
        );

        return $commit;
    }
}
