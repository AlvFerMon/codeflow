<?php

namespace App\Policies;

use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\User;
use App\Policies\Concerns\ChecksProjectRoles;
use App\Support\Roles;
use Illuminate\Auth\Access\Response;

class PullRequestPolicy
{
    use ChecksProjectRoles;

    public function create(?User $user, Repository $repository): Response
    {
        return $this->requireRole($user, $repository->project, Roles::COLLABORATOR, $repository, 'No tienes permisos para abrir Pull Requests.');
    }

    /** Editar título, descripción y revisores: el autor (colaborador) o el propietario. */
    public function update(?User $user, PullRequest $pullRequest): Response
    {
        return $this->ownerOrAuthor($user, $pullRequest, 'Solo el autor o el propietario pueden editar el Pull Request.');
    }

    /** Comentario general (fuera del código): cualquier miembro, observadores incluidos. */
    public function comment(?User $user, PullRequest $pullRequest): Response
    {
        $repository = $pullRequest->repository;

        return $this->requireRole($user, $repository->project, Roles::OBSERVER, $repository, 'Solo los miembros del proyecto pueden comentar.');
    }

    /** Comentario en una línea del código: colaboradores y propietario. */
    public function commentOnCode(?User $user, PullRequest $pullRequest): Response
    {
        $repository = $pullRequest->repository;

        return $this->requireRole($user, $repository->project, Roles::COLLABORATOR, $repository, 'Los observadores solo pueden hacer comentarios generales, no sobre el código.');
    }

    /** Aprobar, pedir cambios o comentar como revisión: colaboradores y propietario, nunca en el PR propio. */
    public function review(?User $user, PullRequest $pullRequest): Response
    {
        $repository = $pullRequest->repository;
        $response = $this->requireRole($user, $repository->project, Roles::COLLABORATOR, $repository, 'No tienes permisos para revisar Pull Requests.');

        if ($response->allowed() && $pullRequest->author_id === $user->id) {
            return Response::deny('No puedes revisar tu propio Pull Request.');
        }

        return $response;
    }

    /** Fusionar: el propietario siempre; un colaborador solo si el PR está aprobado. */
    public function merge(?User $user, PullRequest $pullRequest): Response
    {
        $repository = $pullRequest->repository;
        $project = $repository->project;

        if ($this->isRole($user, $project, Roles::COLLABORATOR)) {
            return $pullRequest->status === 'approved'
                ? Response::allow()
                : Response::deny('El Pull Request necesita al menos una aprobación para fusionarse.');
        }

        return $this->requireRole($user, $project, Roles::OWNER, $repository, 'No tienes permisos para fusionar Pull Requests.');
    }

    /** Lanzar una revisión con IA: colaboradores y propietario (los observadores solo ven los resultados). */
    public function analyze(?User $user, PullRequest $pullRequest): Response
    {
        $repository = $pullRequest->repository;

        return $this->requireRole($user, $repository->project, Roles::COLLABORATOR, $repository, 'Solo los colaboradores y el propietario pueden analizar el Pull Request con IA.');
    }

    /** Cerrar o reabrir: el propietario o el autor. */
    public function close(?User $user, PullRequest $pullRequest): Response
    {
        return $this->ownerOrAuthor($user, $pullRequest, 'Solo el autor o el propietario pueden cerrar o reabrir el Pull Request.');
    }

    private function ownerOrAuthor(?User $user, PullRequest $pullRequest, string $message): Response
    {
        $repository = $pullRequest->repository;
        $project = $repository->project;

        if ($this->isRole($user, $project, Roles::COLLABORATOR) && $pullRequest->author_id === $user->id) {
            return Response::allow();
        }

        return $this->requireRole($user, $project, Roles::OWNER, $repository, $message);
    }
}
