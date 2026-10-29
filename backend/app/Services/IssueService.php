<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Project;
use App\Models\Repository;
use App\Models\User;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;

class IssueService
{
    private const STATUS_LABELS = [
        'open' => 'abierta',
        'in_progress' => 'en progreso',
        'resolved' => 'resuelta',
        'closed' => 'cerrada',
    ];

    public function __construct(private NotificationService $notifications) {}

    /** @param  array{title:string,description?:?string,priority?:?string,assignee_id?:?int,labels?:?array}  $data */
    public function create(Repository $repository, User $user, array $data): Issue
    {
        $project = $repository->project;

        if (! empty($data['assignee_id'])) {
            $this->ensureMember($project, (int) $data['assignee_id']);
        }

        $issue = DB::transaction(function () use ($repository, $data, $user, $project) {
            // Se bloquea la fila del repositorio (no el MAX: PostgreSQL no admite FOR UPDATE con agregados)
            // para que dos peticiones a la vez no saquen el mismo número
            Repository::whereKey($repository->id)->lockForUpdate()->first();
            $number = (int) Issue::where('repository_id', $repository->id)->max('number') + 1;

            $issue = Issue::create([
                'repository_id' => $repository->id,
                'number' => $number,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'author_id' => $user->id,
                'assignee_id' => $data['assignee_id'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'open',
                'labels' => $data['labels'] ?? [],
            ]);

            Activity::log($project, $user, 'issue.created', $issue, ['number' => $issue->number, 'title' => $issue->title]);

            return $issue;
        });

        $this->notifications->notifyProject(
            $project, $user, 'issue.created',
            "{$user->name} creó la issue #{$issue->number} «{$issue->title}» en el repositorio {$repository->name}",
            $issue, repository: $repository,
        );

        return $issue;
    }

    /** Edita la issue; avisa a los miembros si cambia el estado o la persona asignada. */
    public function update(Issue $issue, User $user, array $data): Issue
    {
        $repository = $issue->repository;
        $project = $repository->project;

        $data = array_intersect_key($data, array_flip(['title', 'description', 'assignee_id', 'priority', 'status', 'labels']));

        if (! empty($data['assignee_id'])) {
            $this->ensureMember($project, (int) $data['assignee_id']);
        }

        $before = $issue->only(array_keys($data));
        $issue->fill($data);
        $dirty = array_keys($issue->getDirty());
        $issue->save();

        if (! $dirty) {
            return $issue;
        }

        $changes = [];
        foreach ($dirty as $field) {
            $changes[$field] = ['from' => $before[$field] ?? null, 'to' => $issue->{$field}];
        }
        Activity::log($project, $user, 'issue.updated', $issue, ['changes' => $changes]);

        $statusChanged = in_array('status', $dirty, true);
        $assigneeChanged = in_array('assignee_id', $dirty, true);

        if ($statusChanged || $assigneeChanged) {
            $ref = "la issue #{$issue->number} «{$issue->title}»";
            $parts = [];

            if ($statusChanged) {
                $parts[] = "cambió el estado de {$ref} a ".(self::STATUS_LABELS[$issue->status] ?? $issue->status);
            }
            if ($assigneeChanged) {
                $assignee = $issue->assignee_id ? User::find($issue->assignee_id) : null;
                $target = $statusChanged ? 'la' : $ref;
                $parts[] = $assignee ? "asignó {$target} a {$assignee->name}" : "quitó la asignación de {$target}";
            }

            $event = match (true) {
                $statusChanged && $assigneeChanged => 'issue.updated',
                $statusChanged => 'issue.status_changed',
                default => 'issue.assigned',
            };

            $this->notifications->notifyProject(
                $project, $user, $event, "{$user->name} ".implode(' y ', $parts),
                $issue, repository: $repository,
            );
        }

        return $issue;
    }

    /** Comentario general. Si lo escribe un observador, el resto de miembros recibe una alerta. */
    public function comment(Issue $issue, User $user, string $body): IssueComment
    {
        $repository = $issue->repository;
        $project = $repository->project;

        $comment = IssueComment::create(['issue_id' => $issue->id, 'user_id' => $user->id, 'body' => $body]);
        Activity::log($project, $user, 'issue.commented', $issue, ['number' => $issue->number]);

        $this->notifications->notifyProject(
            $project, $user, 'issue.commented',
            "{$user->name} comentó en la issue #{$issue->number} «{$issue->title}»",
            $issue,
            alert: $user->roleIn($project) === Roles::OBSERVER,
            repository: $repository,
        );

        return $comment;
    }

    public function delete(Issue $issue, User $user): void
    {
        Activity::log($issue->repository->project, $user, 'issue.deleted', null, [
            'number' => $issue->number, 'title' => $issue->title, 'repository_id' => $issue->repository_id,
        ]);
        $issue->delete();
    }

    private function ensureMember(Project $project, int $userId): void
    {
        abort_unless($project->hasMember($userId), 422, 'El usuario indicado no pertenece al proyecto.');
    }
}
