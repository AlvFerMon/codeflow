<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Concerns\ChecksProjectRoles;
use App\Support\Roles;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    use ChecksProjectRoles;

    /** Listar "mis proyectos": cualquier usuario autenticado. */
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    /** Ver el proyecto, su panel, miembros y actividad: solo miembros (404 para el resto). */
    public function view(?User $user, Project $project): Response
    {
        return $this->isMember($user, $project) ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(?User $user): bool
    {
        return $user !== null;
    }

    public function update(?User $user, Project $project): Response
    {
        return $this->requireRole($user, $project, Roles::OWNER, message: 'Solo el propietario puede editar el proyecto.');
    }

    public function delete(?User $user, Project $project): Response
    {
        return $this->requireRole($user, $project, Roles::OWNER, message: 'Solo el propietario puede eliminar el proyecto.');
    }

    /** Añadir, cambiar el rol o quitar miembros: solo el propietario (o el admin). */
    public function manageMembers(?User $user, Project $project): Response
    {
        return $this->requireRole($user, $project, Roles::OWNER, message: 'Solo el propietario puede gestionar los miembros del proyecto.');
    }
}
