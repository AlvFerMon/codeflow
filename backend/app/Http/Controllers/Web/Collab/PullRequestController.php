<?php

namespace App\Http\Controllers\Web\Collab;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Collab\Concerns\HandlesServiceErrors;
use App\Models\Branch;
use App\Models\Commit;
use App\Models\Issue;
use App\Models\ProjectMember;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\User;
use App\Services\PullRequestService;
use App\Services\VcsService;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pull Requests de un repositorio en la web. Toda la lógica de negocio vive en PullRequestService. */
class PullRequestController extends Controller
{
    use HandlesServiceErrors;

    /** Eventos del historial que ya se muestran de otra forma (comentarios, revisiones, cabecera). */
    private const HIDDEN_EVENTS = [
        'pull_request.opened', 'pull_request.commented',
        'pull_request.approved', 'pull_request.changes_requested',
    ];

    public function __construct(
        private PullRequestService $pullRequests,
        private VcsService $vcs,
    ) {}

    public function index(Request $request, Repository $repository): View
    {
        Gate::authorize('view', $repository);

        $statuses = array_keys(CollabLabels::PR_STATUSES);
        $filters = [
            'status' => in_array($request->query('status'), $statuses, true) ? $request->query('status') : null,
            'author' => ctype_digit((string) $request->query('author')) ? (int) $request->query('author') : null,
        ];

        $state = $filters['status']
            ? (in_array($filters['status'], CollabLabels::PR_CLOSED_STATES, true) ? 'closed' : 'open')
            : ($request->query('state') === 'closed' ? 'closed' : 'open');

        $base = fn () => PullRequest::where('repository_id', $repository->id)
            ->when($filters['author'], fn ($q, $v) => $q->where('author_id', $v));

        $counts = [
            'open' => $base()->whereIn('status', PullRequest::OPEN_STATES)->count(),
            'closed' => $base()->whereIn('status', CollabLabels::PR_CLOSED_STATES)->count(),
        ];

        $pulls = $base()
            ->when(
                $filters['status'],
                fn ($q, $v) => $q->where('status', $v),
                fn ($q) => $q->whereIn('status', $state === 'closed' ? CollabLabels::PR_CLOSED_STATES : PullRequest::OPEN_STATES),
            )
            ->with(['author:id,name', 'reviewers:id,name'])
            ->withCount('comments')
            ->orderByDesc('number')
            ->paginate(25)
            ->withQueryString();

        $authors = User::whereIn('id', PullRequest::where('repository_id', $repository->id)->select('author_id'))
            ->orderBy('name')->pluck('name', 'id')->all();

        return view('pulls.index', [
            'repository' => $repository,
            'pulls' => $pulls,
            'filters' => $filters,
            'state' => $state,
            'counts' => $counts,
            'authors' => $authors,
            'hasFilters' => $filters['status'] || $filters['author'],
        ]);
    }

    /** Formulario con selección de ramas (GET ?source=&target= por nombre) y vista previa del diff. */
    public function create(Request $request, Repository $repository): View
    {
        Gate::authorize('create', [PullRequest::class, $repository]);

        $branches = Branch::where('repository_id', $repository->id)->orderBy('name')->get();
        $byName = $branches->keyBy('name');

        $target = $byName->get((string) $request->query('target'))
            ?? $byName->get($repository->default_branch)
            ?? $branches->first();
        $source = $byName->get((string) $request->query('source'));

        $files = null;
        $existing = null;
        $problem = null;

        if ($source && $target) {
            if ($source->id === $target->id) {
                $problem = 'La rama origen y la de destino deben ser distintas.';
            } elseif (! $source->head_commit_id) {
                $problem = 'La rama origen no tiene commits.';
            } else {
                $files = $this->vcs->compare($repository->id, $target->head_commit_id, $source->head_commit_id, true);
                if ($files === []) {
                    $problem = "No hay diferencias: «{$source->name}» no aporta cambios nuevos a «{$target->name}».";
                }
                $existing = PullRequest::where('repository_id', $repository->id)
                    ->where('source_branch_id', $source->id)->where('target_branch_id', $target->id)
                    ->whereIn('status', PullRequest::OPEN_STATES)->first();
            }
        }

        $reviewers = $repository->project->members()
            ->wherePivotIn('role', [Roles::OWNER, Roles::COLLABORATOR])
            ->where('users.id', '!=', $request->user()->id)
            ->orderBy('name')->get(['users.id', 'users.name']);

        $openIssues = Issue::where('repository_id', $repository->id)
            ->whereIn('status', CollabLabels::ISSUE_OPEN_STATES)
            ->orderByDesc('number')->get(['id', 'number', 'title', 'status']);

        return view('pulls.create', [
            'repository' => $repository,
            'branches' => $branches,
            'source' => $source,
            'target' => $target,
            'files' => $files,
            'problem' => $problem,
            'existing' => $existing,
            'reviewers' => $reviewers,
            'openIssues' => $openIssues,
        ]);
    }

    public function store(Request $request, Repository $repository): RedirectResponse
    {
        Gate::authorize('create', [PullRequest::class, $repository]);

        $branchRule = Rule::exists('branches', 'id')->where('repository_id', $repository->id);
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:20000',
            'source_branch_id' => ['required', 'integer', $branchRule],
            'target_branch_id' => ['required', 'integer', 'different:source_branch_id', $branchRule],
            'reviewer_ids' => 'nullable|array|max:20',
            'reviewer_ids.*' => 'integer|exists:users,id',
            'issue_ids' => 'nullable|array|max:50',
            'issue_ids.*' => 'integer',
        ], self::VALIDATION_MESSAGES, self::VALIDATION_ATTRIBUTES);

        return $this->attempt(
            fn () => $this->pullRequests->open($repository, $request->user(), [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'source_branch_id' => (int) $data['source_branch_id'],
                'target_branch_id' => (int) $data['target_branch_id'],
                'reviewers' => $data['reviewer_ids'] ?? [],
                'issues' => $data['issue_ids'] ?? [],
            ]),
            fn (PullRequest $pr) => redirect()
                ->route('pulls.show', [$repository, $pr->number])
                ->with('status', "Pull request #{$pr->number} abierto."),
        );
    }

    public function show(Request $request, Repository $repository, int $number): View
    {
        Gate::authorize('view', $repository);

        $pr = $this->findPullRequest($repository, $number);
        $pr->load([
            'author:id,name',
            'reviewers:id,name',
            'reviews.user:id,name',
            'comments.user:id,name',
            'issues' => fn ($q) => $q->orderBy('number'),
            'sourceBranch:id,name,head_commit_id',
            'targetBranch:id,name,head_commit_id',
            'activity.user:id,name',
        ]);

        $tab = $request->query('tab') === 'files' ? 'files' : 'conversation';
        $files = $this->pullRequests->files($pr, $tab === 'files');

        return view('pulls.show', [
            'repository' => $repository,
            'pr' => $pr,
            'tab' => $tab,
            'files' => $files,
            'commitCount' => $this->commitCount($pr),
            'mergeability' => $pr->isOpen() ? $this->pullRequests->mergeability($pr) : null,
            'mergedBy' => $pr->merged_by ? User::find($pr->merged_by, ['id', 'name']) : null,
            'timeline' => $tab === 'conversation' ? $this->timeline($pr) : collect(),
            'reviewStates' => $this->latestReviewStates($pr),
            'roles' => ProjectMember::where('project_id', $repository->project_id)->pluck('role', 'user_id'),
            'mergeDecision' => $request->user() ? Gate::inspect('merge', $pr) : null,
        ]);
    }

    public function comment(Request $request, Repository $repository, int $number): RedirectResponse
    {
        $pr = $this->findPullRequest($repository, $number);
        Gate::authorize('comment', $pr);

        $data = $request->validate([
            'body' => 'required|string|max:10000',
            'path' => 'nullable|string|max:255',
            'line' => 'nullable|integer|min:1',
        ], self::VALIDATION_MESSAGES, self::VALIDATION_ATTRIBUTES);

        // Igual que la API: comentar sobre el código exige más permisos que un comentario general
        if (isset($data['path']) || isset($data['line'])) {
            Gate::authorize('commentOnCode', $pr);
        }

        return $this->attempt(
            fn () => $this->pullRequests->comment(
                $pr, $request->user(), $data['body'],
                $data['path'] ?? null,
                isset($data['line']) ? (int) $data['line'] : null,
            ),
            fn ($comment) => redirect()
                ->to(route('pulls.show', [$repository, $pr->number]).'#comment-'.$comment->id)
                ->with('status', 'Comentario publicado.'),
        );
    }

    public function review(Request $request, Repository $repository, int $number): RedirectResponse
    {
        $pr = $this->findPullRequest($repository, $number);
        Gate::authorize('review', $pr);

        $data = $request->validate([
            'state' => ['required', Rule::in(PullRequestService::REVIEW_STATES)],
            'body' => 'nullable|string|max:10000|required_if:state,changes_requested',
        ], self::VALIDATION_MESSAGES + ['body.required_if' => 'Explica qué cambios solicitas.'], self::VALIDATION_ATTRIBUTES);

        return $this->attempt(
            fn () => $this->pullRequests->review($pr, $request->user(), $data['state'], $data['body'] ?? null),
            fn () => redirect()->route('pulls.show', [$repository, $pr->number])->with('status', 'Revisión enviada.'),
        );
    }

    public function merge(Request $request, Repository $repository, int $number): RedirectResponse
    {
        $pr = $this->findPullRequest($repository, $number);
        Gate::authorize('merge', $pr);

        // Se comprueba antes para mostrar la lista de conflictos sin depender del 409 del motor
        $check = $this->pullRequests->mergeability($pr);
        if ($check['mergeable'] === false) {
            return $this->failed('No se puede fusionar: hay conflictos en '.implode(', ', $check['conflicts']).'. Resuélvelos en la rama origen.');
        }

        return $this->attempt(
            fn () => $this->pullRequests->merge($pr, $request->user()),
            fn (Commit $commit) => redirect()
                ->route('pulls.show', [$repository, $pr->number])
                ->with('status', "Pull request fusionado en {$pr->target_branch_name} (commit ".substr($commit->sha, 0, 7).').'),
        );
    }

    public function close(Request $request, Repository $repository, int $number): RedirectResponse
    {
        $pr = $this->findPullRequest($repository, $number);
        Gate::authorize('close', $pr);

        return $this->attempt(
            fn () => $this->pullRequests->close($pr, $request->user()),
            fn () => redirect()->route('pulls.show', [$repository, $pr->number])->with('status', 'Pull request cerrado.'),
        );
    }

    public function reopen(Request $request, Repository $repository, int $number): RedirectResponse
    {
        $pr = $this->findPullRequest($repository, $number);
        Gate::authorize('close', $pr);

        return $this->attempt(
            fn () => $this->pullRequests->reopen($pr, $request->user()),
            fn () => redirect()->route('pulls.show', [$repository, $pr->number])->with('status', 'Pull request reabierto.'),
        );
    }

    private function findPullRequest(Repository $repository, int $number): PullRequest
    {
        return PullRequest::where('repository_id', $repository->id)->where('number', $number)->firstOrFail();
    }

    /** Commits que aporta la rama origen (o que aportó, si ya está fusionado). */
    private function commitCount(PullRequest $pr): int
    {
        if ($pr->status === 'merged' && $pr->merge_commit_id) {
            $merge = Commit::find($pr->merge_commit_id, ['id', 'parent_id', 'second_parent_id']);
            if (! $merge) {
                return 0;
            }
            [$base, $head] = $merge->second_parent_id ? [$merge->parent_id, $merge->second_parent_id] : [null, $merge->parent_id];
        } else {
            $head = $pr->sourceBranch?->head_commit_id;
            $base = $pr->targetBranch?->head_commit_id;
        }

        if (! $head) {
            return 0;
        }

        $graph = $this->vcs->graph($pr->repository_id);

        return count(array_diff_key($this->vcs->ancestors($graph, $head), $this->vcs->ancestors($graph, $base)));
    }

    /** Comentarios, revisiones y eventos (cierre, reapertura, fusión...) en orden cronológico. */
    private function timeline(PullRequest $pr): Collection
    {
        $comments = $pr->comments->map(fn ($c) => ['type' => 'comment', 'at' => $c->created_at, 'order' => 1, 'id' => $c->id, 'item' => $c]);
        $reviews = $pr->reviews->map(fn ($r) => ['type' => 'review', 'at' => $r->created_at, 'order' => 2, 'id' => $r->id, 'item' => $r]);
        $events = $pr->activity
            ->reject(fn ($log) => in_array($log->action, self::HIDDEN_EVENTS, true))
            ->map(fn ($log) => ['type' => 'event', 'at' => $log->created_at, 'order' => 3, 'id' => $log->id, 'item' => $log]);

        return $comments->concat($reviews)->concat($events)
            ->sortBy([fn ($a, $b) => $a['at'] <=> $b['at'], fn ($a, $b) => [$a['order'], $a['id']] <=> [$b['order'], $b['id']]])
            ->values();
    }

    /**
     * Revisores (pedidos o espontáneos) con el estado de su última revisión.
     *
     * @return Collection<int,array{user:User,state:?string,requested:bool}>
     */
    private function latestReviewStates(PullRequest $pr): Collection
    {
        $latest = $pr->reviews->sortBy('id')->groupBy('user_id')->map(fn ($reviews) => $reviews->last());

        $rows = $pr->reviewers->map(fn (User $u) => [
            'user' => $u, 'state' => $latest->get($u->id)?->state, 'requested' => true,
        ])->keyBy(fn ($row) => $row['user']->id);

        foreach ($latest as $userId => $review) {
            if (! $rows->has($userId) && $review->user) {
                $rows->put($userId, ['user' => $review->user, 'state' => $review->state, 'requested' => false]);
            }
        }

        return $rows->values();
    }
}
