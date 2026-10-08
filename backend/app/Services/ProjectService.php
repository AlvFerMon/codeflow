<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    /** Crea el proyecto y da de alta a su creador como propietario. */
    public function create(User $user, array $data): Project
    {
        return DB::transaction(function () use ($user, $data) {
            $project = Project::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'settings' => $data['settings'] ?? null,
                'owner_id' => $user->id,
            ]);

            ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id, 'role' => Roles::OWNER]);
            $user->forgetProjectRoles();

            Activity::log($project, $user, 'project.created', $project);

            return $project;
        });
    }

    public function update(Project $project, User $user, array $data): Project
    {
        $project->fill(array_intersect_key($data, array_flip(['name', 'description', 'settings'])));
        $changed = array_keys($project->getDirty());
        $project->save();

        if ($changed) {
            Activity::log($project, $user, 'project.updated', $project, ['fields' => $changed]);
        }

        return $project;
    }

    /** Elimina el proyecto con todo su contenido (repositorios, issues, PRs, actividad). */
    public function delete(Project $project, User $user): void
    {
        $project->delete();
    }
}
