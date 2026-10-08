<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Commit;
use App\Models\Issue;
use App\Models\Project;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\User;
use App\Notifications\ProjectActivityNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Genera las notificaciones de la bandeja de entrada de los miembros de un proyecto.
 */
class NotificationService
{
    /** Títulos de cada tipo de evento. */
    private const TITLES = [
        'member.added' => 'Nuevo acceso a un proyecto',
        'member.role_changed' => 'Tu rol ha cambiado',
        'repository.created' => 'Nuevo repositorio',
        'branch.created' => 'Nueva rama',
        'commit.created' => 'Nuevos cambios',
        'issue.created' => 'Nueva issue',
        'issue.status_changed' => 'Cambio de estado en una issue',
        'issue.assigned' => 'Cambio de asignación en una issue',
        'issue.updated' => 'Issue actualizada',
        'issue.commented' => 'Nuevo comentario en una issue',
        'pull_request.opened' => 'Nuevo Pull Request',
        'pull_request.commented' => 'Nuevo comentario en un Pull Request',
        'pull_request.approved' => 'Pull Request aprobado',
        'pull_request.changes_requested' => 'Cambios solicitados en un Pull Request',
        'pull_request.merged' => 'Pull Request fusionado',
        'pull_request.closed' => 'Pull Request cerrado',
        'pull_request.reopened' => 'Pull Request reabierto',
    ];

    /** Notifica a todos los miembros activos del proyecto excepto a quien hizo el cambio. */
    public function notifyProject(
        Project $project,
        ?User $actor,
        string $event,
        string $message,
        ?Model $subject = null,
        bool $alert = false,
        ?string $url = null,
        ?Repository $repository = null,
    ): void {
        $recipients = $project->members()
            ->where('users.is_active', true)
            ->when($actor, fn ($q) => $q->where('users.id', '!=', $actor->id))
            ->get();

        $this->send($recipients, $project, $actor, $event, $message, $subject, $alert, $url, $repository);
    }

    /** Notifica solo a un usuario concreto (p. ej. al que se le da acceso o se le cambia el rol). */
    public function notifyUser(
        User $recipient,
        Project $project,
        ?User $actor,
        string $event,
        string $message,
        ?Model $subject = null,
        ?string $url = null,
        ?Repository $repository = null,
    ): void {
        if ($actor && $actor->id === $recipient->id) {
            return;
        }

        $this->send([$recipient], $project, $actor, $event, $message, $subject, false, $url, $repository);
    }

    /** Ruta web relativa del recurso, según los patrones de las rutas de la interfaz web. */
    public function urlFor(?Model $subject, Project $project, ?Repository $repository = null): string
    {
        return match (true) {
            $subject instanceof Issue => "/repositories/{$subject->repository_id}/issues/{$subject->number}",
            $subject instanceof PullRequest => "/repositories/{$subject->repository_id}/pulls/{$subject->number}",
            $subject instanceof Commit => "/repositories/{$subject->repository_id}/commits/{$subject->sha}",
            $subject instanceof Branch => "/repositories/{$subject->repository_id}",
            $subject instanceof Repository => "/repositories/{$subject->id}",
            $repository !== null => "/repositories/{$repository->id}",
            default => "/projects/{$project->id}",
        };
    }

    private function send(iterable $recipients, Project $project, ?User $actor, string $event, string $message, ?Model $subject, bool $alert, ?string $url, ?Repository $repository): void
    {
        $recipients = collect($recipients);

        if ($recipients->isEmpty()) {
            return;
        }

        $repositoryId = $repository?->id ?? match (true) {
            $subject instanceof Repository => $subject->id,
            $subject !== null && $subject->getAttribute('repository_id') !== null => (int) $subject->getAttribute('repository_id'),
            default => null,
        };

        $kind = $alert ? ProjectActivityNotification::KIND_ALERT : ProjectActivityNotification::KIND_CHANGE;

        Notification::send($recipients, new ProjectActivityNotification([
            'kind' => $kind,
            'event' => $event,
            'title' => $alert ? 'Comentario de un observador' : (self::TITLES[$event] ?? 'Cambios en el proyecto'),
            'message' => $message,
            'project_id' => $project->id,
            'project_name' => $project->name,
            'repository_id' => $repositoryId,
            'subject_type' => $subject ? Str::snake(class_basename($subject)) : null,
            'subject_id' => $subject?->getKey(),
            'url' => $url ?? $this->urlFor($subject, $project, $repository),
            'actor' => $actor ? ['id' => $actor->id, 'name' => $actor->name] : null,
        ]));
    }
}
