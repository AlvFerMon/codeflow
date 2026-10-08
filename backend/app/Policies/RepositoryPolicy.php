<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Repository;
use App\Models\User;
use App\Policies\Concerns\ChecksProjectRoles;
use App\Support\Roles;
use Illuminate\Auth\Access\Response;

class RepositoryPolicy
{
    use ChecksProjectRoles;

    /** Repos públicos: cualquiera (también invitados). Privados: solo miembros; el resto recibe 404. */
    public function view(?User $user, Repository $repository): Response
    {
        return $this->canSee($user, $repository) ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(?User $user, Project $project): Response
    {
        return $this->requireRole($user, $project, Roles::OWNER, message: 'Solo el propietario puede crear repositorios.');
    }

    /** Editar nombre, descripción y visibilidad. */
    public function update(?User $user, Repository $repository): Response
    {
        return $this->requireRole($user, $repository->project, Roles::OWNER, $repository, 'Solo el propietario puede editar el repositorio.');
    }

    public function delete(?User $user, Repository $repository): Response
    {
        return $this->requireRole($user, $repository->project, Roles::OWNER, $repository, 'Solo el propietario puede eliminar el repositorio.');
    }

    /** Crear y borrar ramas y subir commits. */
    public function push(?User $user, Repository $repository): Response
    {
        return $this->requireRole($user, $repository->project, Roles::COLLABORATOR, $repository, 'No tienes permisos para modificar el código de este repositorio.');
    }
}
