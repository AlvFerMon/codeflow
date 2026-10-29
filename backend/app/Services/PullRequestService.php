<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Commit;
use App\Models\Issue;
use App\Models\Project;
use App\Models\PullRequest;
use App\Models\PullRequestComment;
use App\Models\PullRequestReview;
use App\Models\Repository;
use App\Models\User;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;

class PullRequestService
{
    public const REVIEW_STATES = ['approved', 'changes_requested', 'commented'];

    public function __construct(
        private VcsService $vcs,
        private NotificationService $notifications,
    ) {}

    /**
     * Abre un Pull Request de la rama origen hacia la rama destino.
     *
     * @param  array{title:string,description?:?string,source_branch_id:int,target_branch_id:int,reviewers?:array<int>,issues?:array<int>}  $data
     */
    public function open(Repository $repository, User $user, array $data): PullRequest
    {
        $project = $repository->project;

        abort_if((int) $data['source_branch_id'] === (int) $data['target_branch_id'], 422, 'La rama origen y la de destino deben ser distintas.');

        $source = Branch::where('repository_id', $repository->id)->findOrFail($data['source_branch_id']);
        $target = Branch::where('repository_id', $repository->id)->findOrFail($data['target_branch_id']);

        abort_unless($source->head_commit_id, 422, 'La rama origen no tiene commits.');
        $this->ensureNoDuplicate($repository, $source, $target);

        if ($target->head_commit_id) {
            $included = isset($this->vcs->ancestors($this->vcs->graph($repository->id), $target->head_commit_id)[$source->head_commit_id]);
            abort_if($included, 422, 'La rama origen no aporta cambios nuevos a la rama destino.');
        }

        $reviewerIds = $this->validReviewers($project, $data['reviewers'] ?? $data['reviewer_ids'] ?? [], $user->id);
        $issueIds = Issue::where('repository_id', $repository->id)
            ->whereIn('id', $data['issues'] ?? $data['issue_ids'] ?? [])
            ->pluck('id')->all();

        $pr = DB::transaction(function () use ($repository, $data, $source, $target, $user, $project, $reviewerIds, $issueIds) {
            // Se bloquea la fila del repositorio (no el MAX: PostgreSQL no admite FOR UPDATE con agregados)
            // para que dos peticiones a la vez no saquen el mismo número
            Repository::whereKey($repository->id)->lockForUpdate()->first();
            $number = (int) PullRequest::where('repository_id', $repository->id)->max('number') + 1;

            $pr = PullRequest::create([
                'repository_id' => $repository->id,
                'number' => $number,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'author_id' => $user->id,
                'source_branch_id' => $source->id,
                'target_branch_id' => $target->id,
                'source_branch_name' => $source->name,
                'target_branch_name' => $target->name,
                'status' => $reviewerIds ? 'in_review' : 'open',
            ]);

            $pr->reviewers()->sync($reviewerIds);
            $pr->issues()->sync($issueIds);

            Activity::log($project, $user, 'pull_request.opened', $pr, [
                'number' => $pr->number,
                'title' => $pr->title,
                'from' => $source->name,
                'to' => $target->name,
            ]);

            return $pr;
        });

        $this->notify($pr, $user, 'pull_request.opened',
            "{$user->name} abrió el Pull Request #{$pr->number} «{$pr->title}» de {$source->name} a {$target->name}");

        return $pr;
    }

    /** Edita título, descripción y revisores de un PR abierto. */
    public function update(PullRequest $pullRequest, User $user, array $data): PullRequest
    {
        abort_unless($pullRequest->isOpen(), 422, 'El Pull Request ya está cerrado.');

        $reviewers = array_key_exists('reviewers', $data) ? $data['reviewers'] : ($data['reviewer_ids'] ?? null);
        $hasReviewers = array_key_exists('reviewers', $data) || array_key_exists('reviewer_ids', $data);

        if ($hasReviewers) {
            $ids = $this->validReviewers($pullRequest->repository->project, $reviewers ?? [], $pullRequest->author_id);
            $pullRequest->reviewers()->sync($ids);
            if ($pullRequest->status === 'open' && $ids) {
                $pullRequest->status = 'in_review';
            }
        }

        $pullRequest->fill(array_intersect_key($data, array_flip(['title', 'description'])));
        $dirty = array_keys($pullRequest->getDirty());
        $pullRequest->save();

        if ($dirty || $hasReviewers) {
            Activity::log($pullRequest->repository->project, $user, 'pull_request.updated', $pullRequest, [
                'number' => $pullRequest->number,
                'fields' => array_values(array_unique(array_merge($dirty, $hasReviewers ? ['reviewers'] : []))),
            ]);
        }

        return $pullRequest;
    }

    /**
     * Comentario general o, con $path (y opcionalmente $line), sobre el código.
     * Si lo escribe un observador, el resto de miembros recibe una alerta.
     */
    public function comment(PullRequest $pullRequest, User $user, string $body, ?string $path = null, ?int $line = null): PullRequestComment
    {
        $project = $pullRequest->repository->project;

        abort_if($line !== null && $path === null, 422, 'Para comentar una línea hay que indicar el archivo.');

        $comment = PullRequestComment::create([
            'pull_request_id' => $pullRequest->id,
            'user_id' => $user->id,
            'body' => $body,
            'path' => $path,
            'line' => $line,
        ]);

        Activity::log($project, $user, 'pull_request.commented', $pullRequest, array_filter([
            'number' => $pullRequest->number,
            'path' => $path,
            'line' => $line,
        ], fn ($v) => $v !== null));

        $this->notify($pullRequest, $user, 'pull_request.commented',
            "{$user->name} comentó en el Pull Request #{$pullRequest->number} «{$pullRequest->title}»",
            alert: $user->roleIn($project) === Roles::OBSERVER);

        return $comment;
    }

    /** Revisión: aprobar, pedir cambios o comentar. El estado se recalcula con la última revisión de cada revisor. */
    public function review(PullRequest $pullRequest, User $user, string $state, ?string $body = null): PullRequestReview
    {
        abort_unless($pullRequest->isOpen(), 422, 'El Pull Request ya está cerrado.');
        abort_unless(in_array($state, self::REVIEW_STATES, true), 422, 'Estado de revisión no válido.');
        abort_if($pullRequest->author_id === $user->id, 422, 'No puedes revisar tu propio Pull Request.');
        abort_if($state === 'changes_requested' && blank($body), 422, 'Explica qué cambios solicitas.');

        $review = PullRequestReview::create([
            'pull_request_id' => $pullRequest->id,
            'user_id' => $user->id,
            'state' => $state,
            'body' => $body,
        ]);

        $pullRequest->update(['status' => $this->computeStatus($pullRequest)]);

        Activity::log($pullRequest->repository->project, $user, "pull_request.{$state}", $pullRequest, ['number' => $pullRequest->number]);

        $ref = "el Pull Request #{$pullRequest->number} «{$pullRequest->title}»";
        match ($state) {
            'approved' => $this->notify($pullRequest, $user, 'pull_request.approved', "{$user->name} aprobó {$ref}"),
            'changes_requested' => $this->notify($pullRequest, $user, 'pull_request.changes_requested', "{$user->name} pidió cambios en {$ref}"),
            default => null,
        };

        return $review;
    }

    /** Fusiona el PR en la rama destino. Las issues vinculadas pasan a "resuelta". */
    public function merge(PullRequest $pullRequest, User $user): Commit
    {
        abort_unless($pullRequest->isOpen(), 422, 'El Pull Request ya está cerrado.');
        abort_if($pullRequest->status === 'changes_requested', 422, 'Hay cambios solicitados pendientes de resolver.');

        $commit = $this->vcs->merge($pullRequest, $user);

        $pullRequest->update([
            'status' => 'merged',
            'merged_by' => $user->id,
            'merged_at' => now(),
            'merge_commit_id' => $commit->id,
        ]);

        $pullRequest->issues()->whereIn('status', ['open', 'in_progress'])->update(['status' => 'resolved']);

        Activity::log($pullRequest->repository->project, $user, 'pull_request.merged', $pullRequest, [
            'number' => $pullRequest->number,
            'from' => $pullRequest->source_branch_name,
            'to' => $pullRequest->target_branch_name,
            'sha' => substr($commit->sha, 0, 7),
        ]);

        $this->notify($pullRequest, $user, 'pull_request.merged',
            "{$user->name} fusionó el Pull Request #{$pullRequest->number} «{$pullRequest->title}» en {$pullRequest->target_branch_name}");

        return $commit;
    }

    public function close(PullRequest $pullRequest, User $user): PullRequest
    {
        abort_unless($pullRequest->isOpen(), 422, 'El Pull Request ya está cerrado.');

        $pullRequest->update(['status' => 'closed']);
        Activity::log($pullRequest->repository->project, $user, 'pull_request.closed', $pullRequest, ['number' => $pullRequest->number]);

        $this->notify($pullRequest, $user, 'pull_request.closed',
            "{$user->name} cerró el Pull Request #{$pullRequest->number} «{$pullRequest->title}»");

        return $pullRequest;
    }

    public function reopen(PullRequest $pullRequest, User $user): PullRequest
    {
        abort_unless($pullRequest->status === 'closed', 422, 'Solo se pueden reabrir Pull Requests cerrados.');

        $source = $pullRequest->sourceBranch;
        $target = $pullRequest->targetBranch;
        abort_unless($source && $target, 422, 'Alguna de las ramas del Pull Request ya no existe.');
        $this->ensureNoDuplicate($pullRequest->repository, $source, $target);

        $pullRequest->update([
            'status' => $pullRequest->reviewers()->exists() || $pullRequest->reviews()->exists()
                ? $this->computeStatus($pullRequest)
                : 'open',
        ]);
        Activity::log($pullRequest->repository->project, $user, 'pull_request.reopened', $pullRequest, ['number' => $pullRequest->number]);

        $this->notify($pullRequest, $user, 'pull_request.reopened',
            "{$user->name} reabrió el Pull Request #{$pullRequest->number} «{$pullRequest->title}»");

        return $pullRequest;
    }

    /**
     * Commits que se comparan en el PR: [base, head]. Si está fusionado, el commit de fusión
     * frente a su primer padre; si no, la base común con el destino frente a la rama origen.
     *
     * @return array{0:?int,1:?int}
     */
    public function diffRange(PullRequest $pullRequest): array
    {
        if ($pullRequest->status === 'merged' && $pullRequest->merge_commit_id) {
            $merge = Commit::find($pullRequest->merge_commit_id, ['id', 'parent_id']);

            return $merge ? [$merge->parent_id, $merge->id] : [null, null];
        }

        $s = $pullRequest->sourceBranch?->head_commit_id;
        $t = $pullRequest->targetBranch?->head_commit_id;

        if (! $s) {
            return [null, null];
        }

        return [$t ? $this->vcs->mergeBase($this->vcs->graph($pullRequest->repository_id), $t, $s) : null, $s];
    }

    /**
     * Archivos modificados por el PR (resumen en caché). Con $withHunks se añaden las líneas,
     * solo de $hunkPaths si se indica.
     */
    public function files(PullRequest $pullRequest, bool $withHunks = false, ?array $hunkPaths = null): array
    {
        [$base, $head] = $this->diffRange($pullRequest);

        return $head ? $this->vcs->diff($base, $head, $withHunks, $hunkPaths) : [];
    }

    /** Diff de un archivo del PR con sus líneas (carga bajo demanda). */
    public function fileDiff(PullRequest $pullRequest, string $path): ?array
    {
        [$base, $head] = $this->diffRange($pullRequest);

        return $head ? $this->vcs->fileDiff($base, $head, $path) : null;
    }

    /**
     * ¿Se puede fusionar sin conflictos? (null si el PR no está abierto).
     *
     * @return array{mergeable:?bool,conflicts:array<int,string>}
     */
    public function mergeability(PullRequest $pullRequest): array
    {
        $source = $pullRequest->sourceBranch;
        $target = $pullRequest->targetBranch;

        if (! $pullRequest->isOpen() || ! $source?->head_commit_id || ! $target) {
            return ['mergeable' => null, 'conflicts' => [], 'auto_merged' => []];
        }

        $plan = $this->vcs->planMerge($this->vcs->graph($pullRequest->repository_id), $target->head_commit_id, $source->head_commit_id);

        return [
            'mergeable' => $plan['conflicts'] === [],
            'conflicts' => $plan['conflicts'],
            // Archivos tocados por las dos ramas pero en líneas distintas: se combinan solos al fusionar
            'auto_merged' => array_keys($plan['merged'] ?? []),
        ];
    }

    /** Estado derivado de la última revisión decisiva de cada revisor. */
    private function computeStatus(PullRequest $pullRequest): string
    {
        $latest = $pullRequest->reviews()->orderBy('id')->get()
            ->groupBy('user_id')
            ->map(fn ($reviews) => $reviews->whereIn('state', ['approved', 'changes_requested'])->last()?->state)
            ->filter()
            ->values();

        if ($latest->contains('changes_requested')) {
            return 'changes_requested';
        }
        if ($latest->contains('approved')) {
            return 'approved';
        }

        return 'in_review';
    }

    /** Revisores válidos: miembros del proyecto y distintos del autor. */
    private function validReviewers(Project $project, array $ids, ?int $authorId): array
    {
        $ids = array_values(array_unique(array_map('intval', array_diff($ids, [$authorId]))));

        foreach ($ids as $id) {
            abort_unless($project->hasMember($id), 422, 'El usuario indicado no pertenece al proyecto.');
        }

        return $ids;
    }

    private function ensureNoDuplicate(Repository $repository, Branch $source, Branch $target): void
    {
        $duplicate = PullRequest::where('repository_id', $repository->id)
            ->where('source_branch_id', $source->id)
            ->where('target_branch_id', $target->id)
            ->whereIn('status', PullRequest::OPEN_STATES)
            ->exists();

        abort_if($duplicate, 422, 'Ya existe un Pull Request abierto entre estas dos ramas.');
    }

    private function notify(PullRequest $pullRequest, User $actor, string $event, string $message, bool $alert = false): void
    {
        $repository = $pullRequest->repository;

        $this->notifications->notifyProject($repository->project, $actor, $event, $message, $pullRequest, $alert, repository: $repository);
    }

    /** Conflictos con detalle para resolverlos a mano (vacío si no hay). */
    public function conflicts(PullRequest $pullRequest): array
    {
        $source = $pullRequest->sourceBranch;
        $target = $pullRequest->targetBranch;
        if (! $pullRequest->isOpen() || ! $source?->head_commit_id || ! $target?->head_commit_id) {
            return [];
        }

        return $this->vcs->conflictDetails(
            $this->vcs->graph($pullRequest->repository_id),
            $source->head_commit_id, $target->head_commit_id,
            $source->name.' (origen)', $target->name.' (destino)',
        );
    }

    /** Resuelve los conflictos con un commit de fusión en la rama origen (ver VcsService::resolveConflicts). */
    public function resolveConflicts(PullRequest $pullRequest, User $user, array $resolutions, array $expected = []): Commit
    {
        abort_unless($pullRequest->isOpen(), 422, 'El Pull Request no está abierto.');

        $commit = $this->vcs->resolveConflicts($pullRequest, $user, $resolutions, $expected);
        $repository = $pullRequest->repository;
        $sha = substr($commit->sha, 0, 7);

        Activity::log($repository->project, $user, 'commit.created', $commit, ['branch' => $pullRequest->source_branch_name, 'sha' => $sha]);
        $pullRequest->touch();
        $this->notify($pullRequest, $user, 'pull_request.updated',
            "{$user->name} resolvió los conflictos del Pull Request #{$pullRequest->number} «{$pullRequest->title}»");

        return $commit;
    }
}
