<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\Repository;
use App\Models\User;
use App\Policies\Concerns\ChecksProjectRoles;
use App\Support\Roles;
use Illuminate\Auth\Access\Response;

class IssuePolicy
{
    use ChecksProjectRoles;

    public function create(?User $user, Repository $repository): Response
    {
        return $this->requireRole($user, $repository->project, Roles::COLLABORATOR, $repository, 'No tienes permisos para crear issues.');
    }

    /** Editar, cambiar el estado o asignar. */
    public function update(?User $user, Issue $issue): Response
    {
        $repository = $issue->repository;

        return $this->requireRole($user, $repository->project, Roles::COLLABORATOR, $repository, 'No tienes permisos para modificar issues.');
    }

    /** Comentar: cualquier miembro, observadores incluidos. */
    public function comment(?User $user, Issue $issue): Response
    {
        $repository = $issue->repository;

        return $this->requireRole($user, $repository->project, Roles::OBSERVER, $repository, 'Solo los miembros del proyecto pueden comentar.');
    }

    /** Eliminar: el propietario, o el autor si sigue siendo colaborador. */
    public function delete(?User $user, Issue $issue): Response
    {
        $repository = $issue->repository;
        $project = $repository->project;

        if ($user !== null && $issue->author_id === $user->id && Roles::level($user->roleIn($project)) >= Roles::level(Roles::COLLABORATOR)) {
            return Response::allow();
        }

        return $this->requireRole($user, $project, Roles::OWNER, $repository, 'Solo el propietario o el autor pueden eliminar la issue.');
    }
}
