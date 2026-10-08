<?php

namespace App\Policies\Concerns;

use App\Models\Project;
use App\Models\Repository;
use App\Models\User;
use App\Support\Roles;
use App\Support\Visibility;
use Illuminate\Auth\Access\Response;

/**
 * Utilidades comunes de las policies. El administrador global no llega aquí:
 * se autoriza antes con Gate::before (AppServiceProvider).
 */
trait ChecksProjectRoles
{
    protected function isMember(?User $user, Project $project): bool
    {
        return $user !== null && $user->roleIn($project) !== null;
    }

    /** Puede ver el repositorio: es público o el usuario es miembro del proyecto. */
    protected function canSee(?User $user, Repository $repository): bool
    {
        return Visibility::isPublic($repository) || $this->isMember($user, $repository->project);
    }

    /**
     * Exige un rol mínimo en el proyecto. Si el usuario ni siquiera puede ver el recurso
     * se responde 404 para no revelar que existe; si puede verlo, 403 con el mensaje dado.
     */
    protected function requireRole(?User $user, Project $project, string $minimum, ?Repository $repository = null, string $message = 'No tienes permisos suficientes en este proyecto.'): Response
    {
        if ($user !== null && Roles::level($user->roleIn($project)) >= Roles::level($minimum)) {
            return Response::allow();
        }

        $visible = $repository ? $this->canSee($user, $repository) : $this->isMember($user, $project);

        return $visible ? Response::deny($message) : Response::denyAsNotFound();
    }

    protected function isRole(?User $user, Project $project, string $role): bool
    {
        return $user !== null && $user->roleIn($project) === $role;
    }
}
