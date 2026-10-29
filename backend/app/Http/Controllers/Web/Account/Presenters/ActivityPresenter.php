<?php

namespace App\Http\Controllers\Web\Account\Presenters;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Commit;
use App\Models\Issue;
use App\Models\Project;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * Convierte los registros de actividad (activity_logs) en frases legibles en español,
 * con enlaces al recurso cuando todavía existe. Todo el texto de usuario se escapa.
 */
final class ActivityPresenter
{
    private const ISSUE_STATUSES = [
        'open' => 'abierta',
        'in_progress' => 'en progreso',
        'resolved' => 'resuelta',
        'closed' => 'cerrada',
    ];

    private const PROJECT_FIELDS = ['name' => 'nombre', 'description' => 'descripción', 'settings' => 'configuración'];

    /** Relaciones necesarias para describir la actividad sin consultas N+1. */
    public static function eagerLoad(Builder $query): Builder
    {
        return $query->with([
            'user:id,name',
            'subject' => fn (MorphTo $morph) => $morph->morphWith([
                Issue::class => ['repository:id,name'],
                PullRequest::class => ['repository:id,name'],
                Commit::class => ['repository:id,name'],
                Branch::class => ['repository:id,name'],
            ]),
        ]);
    }

    /**
     * @param  iterable<ActivityLog>  $logs
     * @return Collection<int,array{id:int,actor:string,sentence:HtmlString,category:string,time:\Illuminate\Support\Carbon,project_id:int}>
     */
    public function presentMany(iterable $logs): Collection
    {
        return collect($logs)->map(fn (ActivityLog $log) => $this->present($log));
    }

    /** @return array{id:int,actor:string,sentence:HtmlString,category:string,time:\Illuminate\Support\Carbon,project_id:int} */
    public function present(ActivityLog $log): array
    {
        $actor = $log->user?->name ?? 'Alguien';

        return [
            'id' => $log->id,
            'actor' => $actor,
            'sentence' => new HtmlString('<span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($actor).'</span> '.$this->describe($log)),
            'category' => strtok($log->action, '.') ?: 'other',
            'time' => $log->created_at,
            'project_id' => $log->project_id,
        ];
    }

    /**
     * Versión para la API (app Android): datos estructurados y la frase en texto plano.
     * Quién (actor), qué (action/summary), dónde (proyecto, repositorio, rama) y cuándo.
     */
    public function presentForApi(ActivityLog $log, ?string $projectName = null): array
    {
        $p = $log->properties ?? [];
        $subject = $log->subject;

        $repository = match (true) {
            $subject instanceof Repository => $subject,
            $subject instanceof Branch, $subject instanceof Commit, $subject instanceof Issue, $subject instanceof PullRequest => $subject->repository,
            default => null,
        };

        $branch = $p['branch']
            ?? ($subject instanceof Branch ? $subject->name : null)
            ?? (str_starts_with($log->action, 'branch.') ? ($p['name'] ?? null) : null)
            ?? (str_starts_with($log->action, 'pull_request.') ? ($p['from'] ?? null) : null);

        return [
            'id' => $log->id,
            'action' => $log->action,
            'category' => strtok($log->action, '.') ?: 'other',
            'summary' => html_entity_decode(strip_tags($this->describe($log)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'actor' => $log->user ? ['id' => $log->user->id, 'name' => $log->user->name] : null,
            'project' => ['id' => $log->project_id, 'name' => $projectName],
            'repository' => $repository ? ['id' => $repository->id, 'name' => $repository->name] : null,
            'branch' => $branch,
            'target_branch' => str_starts_with($log->action, 'pull_request.') ? ($p['to'] ?? null) : null,
            'commit_sha' => $subject instanceof Commit ? substr($subject->sha, 0, 7) : (isset($p['sha']) ? substr((string) $p['sha'], 0, 7) : null),
            'number' => $p['number'] ?? (($subject instanceof Issue || $subject instanceof PullRequest) ? $subject->number : null),
            'properties' => $p,
            'created_at' => $log->created_at?->toJSON(),
        ];
    }

    /** Frase (HTML seguro) sin el nombre del autor. */
    private function describe(ActivityLog $log): string
    {
        $p = $log->properties ?? [];
        $subject = $log->subject;

        return match ($log->action) {
            'project.created' => 'creó el proyecto '.$this->projectLink($subject),
            'project.trashed' => 'movió el proyecto a la papelera',
            'project.restored' => 'restauró el proyecto desde la papelera',
            'repository.restored' => 'restauró el repositorio '.$this->repoLink($subject, $p['name'] ?? null).' desde la papelera',
            'project.updated' => 'actualizó '.$this->fieldList($p['fields'] ?? []).' del proyecto',

            'member.added' => 'dio acceso a '.$this->strong($p['user'] ?? 'un usuario').' como '.e(Roles::label($p['role'] ?? null)),
            'member.role_changed' => 'cambió el rol de '.$this->strong($p['user'] ?? 'un usuario')
                .' de '.e(Roles::label($p['from'] ?? null)).' a '.e(Roles::label($p['to'] ?? null)),
            'member.removed' => 'quitó a '.$this->strong($p['user'] ?? 'un usuario').' del proyecto',
            'member.invited' => 'invitó a '.$this->strong($p['email'] ?? 'alguien').' como '.e(Roles::label($p['role'] ?? null)),
            'member.invitation_cancelled' => 'canceló la invitación de '.$this->strong($p['email'] ?? 'alguien'),
            'member.joined' => 'se unió al proyecto como '.e(Roles::label($p['role'] ?? null)),

            'repository.created' => 'creó el repositorio '.$this->repoLink($subject, $p['name'] ?? null),
            'repository.updated' => $this->describeRepositoryUpdate($subject, $p['changes'] ?? []),
            'repository.deleted' => 'eliminó el repositorio '.$this->code($p['name'] ?? '?'),

            'branch.created' => 'creó la rama '.$this->code($p['name'] ?? $subject?->name ?? '?')
                .(! empty($p['from']) ? ' a partir de '.$this->code($p['from']) : '')
                .$this->inRepo($subject?->repository ?? null),
            'branch.deleted' => 'eliminó la rama '.$this->code($p['name'] ?? '?')
                .$this->inRepo(isset($p['repository_id']) ? Repository::find($p['repository_id']) : null),

            'commit.created' => 'subió el commit '.$this->commitLink($subject, $p['sha'] ?? null)
                .(! empty($p['branch']) ? ' a la rama '.$this->code($p['branch']) : '')
                .$this->inRepo($subject?->repository ?? null),

            'issue.created' => 'abrió la issue '.$this->issueLink($subject, $p['number'] ?? null, $p['title'] ?? null),
            'issue.updated' => $this->describeIssueUpdate($subject, $p['changes'] ?? []),
            'issue.commented' => 'comentó en la issue '.$this->issueLink($subject, $p['number'] ?? null),
            'issue.deleted' => 'eliminó la issue #'.e($p['number'] ?? '?').(! empty($p['title']) ? ' «'.e($p['title']).'»' : ''),

            'pull_request.opened' => 'abrió el pull request '.$this->pullLink($subject, $p['number'] ?? null, $p['title'] ?? null)
                .$this->branches($p),
            'pull_request.updated' => 'editó el pull request '.$this->pullLink($subject, $p['number'] ?? null),
            'pull_request.commented' => 'comentó en el pull request '.$this->pullLink($subject, $p['number'] ?? null)
                .(! empty($p['path']) ? ' (en '.$this->code($p['path'].(isset($p['line']) ? ':'.$p['line'] : '')).')' : ''),
            'pull_request.approved' => 'aprobó el pull request '.$this->pullLink($subject, $p['number'] ?? null),
            'pull_request.changes_requested' => 'pidió cambios en el pull request '.$this->pullLink($subject, $p['number'] ?? null),
            'pull_request.merged' => 'fusionó el pull request '.$this->pullLink($subject, $p['number'] ?? null).$this->branches($p),
            'pull_request.closed' => 'cerró el pull request '.$this->pullLink($subject, $p['number'] ?? null),
            'pull_request.reopened' => 'reabrió el pull request '.$this->pullLink($subject, $p['number'] ?? null),

            default => 'realizó una acción ('.$this->code($log->action).')',
        };
    }

    private function describeRepositoryUpdate(mixed $repository, array $changes): string
    {
        $link = $this->repoLink($repository);

        if (isset($changes['visibility'])) {
            $to = ($changes['visibility']['to'] ?? null) === Repository::VISIBILITY_PUBLIC ? 'público' : 'privado';

            return "cambió la visibilidad del repositorio {$link} a <strong>{$to}</strong>";
        }

        return "actualizó el repositorio {$link}";
    }

    private function describeIssueUpdate(mixed $issue, array $changes): string
    {
        $link = $this->issueLink($issue, $issue?->number ?? null);

        if (isset($changes['status'])) {
            $status = $changes['status']['to'] ?? null;

            return "cambió el estado de la issue {$link} a <strong>".e(self::ISSUE_STATUSES[$status] ?? $status ?? '?').'</strong>';
        }

        if (array_key_exists('assignee_id', $changes)) {
            return "cambió la asignación de la issue {$link}";
        }

        return "editó la issue {$link}";
    }

    private function fieldList(array $fields): string
    {
        $names = array_map(fn ($f) => self::PROJECT_FIELDS[$f] ?? $f, $fields);

        return $names ? 'el '.e(implode(', ', $names)) : 'los datos';
    }

    private function branches(array $p): string
    {
        if (empty($p['from']) || empty($p['to'])) {
            return '';
        }

        return ' ('.$this->code($p['from']).' → '.$this->code($p['to']).')';
    }

    private function projectLink(mixed $project): string
    {
        return $project instanceof Project
            ? $this->link(route('projects.show', $project), $project->name)
            : 'del que forma parte';
    }

    private function repoLink(mixed $repository, ?string $fallbackName = null): string
    {
        if ($repository instanceof Repository) {
            return $this->link(route('repositories.show', $repository), $repository->name, mono: true);
        }

        return $this->code($fallbackName ?? '(eliminado)');
    }

    private function inRepo(?Repository $repository): string
    {
        return $repository ? ' en '.$this->repoLink($repository) : '';
    }

    private function commitLink(mixed $commit, ?string $shortSha): string
    {
        if ($commit instanceof Commit) {
            return $this->link(route('commits.show', [$commit->repository_id, $commit->sha]), substr($commit->sha, 0, 7), mono: true);
        }

        return $this->code($shortSha ?? '?');
    }

    private function issueLink(mixed $issue, mixed $number, ?string $title = null): string
    {
        if ($issue instanceof Issue) {
            return $this->link(route('issues.show', [$issue->repository_id, $issue->number]), "#{$issue->number} «".($title ?? $issue->title).'»');
        }

        return '#'.e($number ?? '?').($title ? ' «'.e($title).'»' : '');
    }

    private function pullLink(mixed $pull, mixed $number, ?string $title = null): string
    {
        if ($pull instanceof PullRequest) {
            return $this->link(route('pulls.show', [$pull->repository_id, $pull->number]), "#{$pull->number} «".($title ?? $pull->title).'»');
        }

        return '#'.e($number ?? '?').($title ? ' «'.e($title).'»' : '');
    }

    private function link(string $url, string $text, bool $mono = false): string
    {
        $class = 'font-medium text-purple-600 dark:text-purple-400 hover:underline'.($mono ? ' font-mono text-[0.9em]' : '');

        return '<a href="'.e($url).'" class="'.$class.'">'.e($text).'</a>';
    }

    private function strong(string $text): string
    {
        return '<span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($text).'</span>';
    }

    private function code(string $text): string
    {
        return '<code class="rounded bg-zinc-100 dark:bg-zinc-800 px-1 font-mono text-[0.85em] text-zinc-700 dark:text-zinc-300">'.e($text).'</code>';
    }
}
