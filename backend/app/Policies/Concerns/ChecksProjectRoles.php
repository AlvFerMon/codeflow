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
    /**
     * Rol efectivo en el proyecto. Un usuario sin email verificado se trata como visitante
     * (sin rol): solo ve lo público hasta que verifique su dirección.
     */
    protected function roleOf(?User $user, Project $project): ?string
    {
        return $user !== null && $user->hasVerifiedEmail() ? $user->roleIn($project) : null;
    }

    protected function isMember(?User $user, Project $project): bool
    {
        return $this->roleOf($user, $project) !== null;
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
        if (Roles::level($this->roleOf($user, $project)) >= Roles::level($minimum)) {
            return Response::allow();
        }

        $visible = $repository ? $this->canSee($user, $repository) : $this->isMember($user, $project);

        return $visible ? Response::deny($message) : Response::denyAsNotFound();
    }

    protected function isRole(?User $user, Project $project, string $role): bool
    {
        return $this->roleOf($user, $project) === $role;
    }
}
