<?php

namespace App\Http\Controllers\Web\Collab;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Collab\Concerns\HandlesServiceErrors;
use App\Models\Issue;
use App\Models\ProjectMember;
use App\Models\Repository;
use App\Models\User;
use App\Services\IssueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Issues de un repositorio en la web. Toda la lógica de negocio vive en IssueService. */
class IssueController extends Controller
{
    use HandlesServiceErrors;

    public function __construct(private IssueService $issues) {}

    public function index(Request $request, Repository $repository): View
    {
        Gate::authorize('view', $repository);

        $filters = [
            'status' => in_array($request->query('status'), Issue::STATUSES, true) ? $request->query('status') : null,
            'priority' => in_array($request->query('priority'), Issue::PRIORITIES, true) ? $request->query('priority') : null,
            'assignee' => ctype_digit((string) $request->query('assignee')) ? (int) $request->query('assignee') : null,
            'label' => is_string($request->query('label')) && $request->query('label') !== '' ? $request->query('label') : null,
            'q' => is_string($request->query('q')) ? trim($request->query('q')) : '',
        ];

        // La pestaña la decide el estado filtrado (si lo hay) o el parámetro "state"
        $state = $filters['status']
            ? (in_array($filters['status'], CollabLabels::ISSUE_CLOSED_STATES, true) ? 'closed' : 'open')
            : ($request->query('state') === 'closed' ? 'closed' : 'open');

        // Mismos filtros que la API (IssueController::index), salvo el estado
        $base = fn () => Issue::where('repository_id', $repository->id)
            ->when($filters['priority'], fn ($q, $v) => $q->where('priority', $v))
            ->when($filters['assignee'], fn ($q, $v) => $q->where('assignee_id', $v))
            ->when($filters['label'], fn ($q, $v) => $q->whereJsonContains('labels', $v))
            ->when($filters['q'] !== '', fn ($q) => $q->where('title', 'like', '%'.$filters['q'].'%'));

        $counts = [
            'open' => $base()->whereIn('status', CollabLabels::ISSUE_OPEN_STATES)->count(),
            'closed' => $base()->whereIn('status', CollabLabels::ISSUE_CLOSED_STATES)->count(),
        ];

        $issues = $base()
            ->when(
                $filters['status'],
                fn ($q, $v) => $q->where('status', $v),
                fn ($q) => $q->whereIn('status', $state === 'closed' ? CollabLabels::ISSUE_CLOSED_STATES : CollabLabels::ISSUE_OPEN_STATES),
            )
            ->with(['author:id,name', 'assignee:id,name'])
            ->withCount('comments')
            ->orderByDesc('number')
            ->paginate(25)
            ->withQueryString();

        $labels = Issue::where('repository_id', $repository->id)->pluck('labels')
            ->flatten()->filter()->unique()->sort()->values();

        return view('issues.index', [
            'repository' => $repository,
            'issues' => $issues,
            'filters' => $filters,
            'state' => $state,
            'counts' => $counts,
            'members' => $this->memberOptions($repository),
            'labels' => $labels,
            'hasFilters' => $filters['status'] || $filters['priority'] || $filters['assignee'] || $filters['label'] || $filters['q'] !== '',
        ]);
    }

    public function create(Repository $repository): View
    {
        Gate::authorize('create', [Issue::class, $repository]);

        return view('issues.create', [
            'repository' => $repository,
            'members' => $this->memberOptions($repository),
        ]);
    }

    public function store(Request $request, Repository $repository): RedirectResponse
    {
        Gate::authorize('create', [Issue::class, $repository]);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:20000',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'priority' => ['nullable', Rule::in(Issue::PRIORITIES)],
            'labels' => 'nullable|string|max:700',
        ], self::VALIDATION_MESSAGES, self::VALIDATION_ATTRIBUTES);

        $data['labels'] = $this->validatedLabels($data['labels'] ?? null);
        if ($data['labels'] instanceof RedirectResponse) {
            return $data['labels'];
        }

        return $this->attempt(
            fn () => $this->issues->create($repository, $request->user(), $data),
            fn (Issue $issue) => redirect()
                ->route('issues.show', [$repository, $issue->number])
                ->with('status', "Issue #{$issue->number} creada."),
        );
    }

    public function show(Repository $repository, int $number): View
    {
        Gate::authorize('view', $repository);

        $issue = $this->findIssue($repository, $number);
        $issue->load([
            'author:id,name',
            'assignee:id,name',
            'comments.user:id,name',
            'pullRequests' => fn ($q) => $q->orderBy('number'),
            'activity.user:id,name',
        ]);

        $roles = ProjectMember::where('project_id', $repository->project_id)->pluck('role', 'user_id');

        return view('issues.show', [
            'repository' => $repository,
            'issue' => $issue,
            'timeline' => $this->timeline($issue),
            'users' => $this->usersInHistory($issue),
            'roles' => $roles,
            'members' => $this->memberOptions($repository),
        ]);
    }

    public function update(Request $request, Repository $repository, int $number): RedirectResponse
    {
        $issue = $this->findIssue($repository, $number);
        Gate::authorize('update', $issue);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:200',
            'description' => 'sometimes|nullable|string|max:20000',
            'assignee_id' => 'sometimes|nullable|integer|exists:users,id',
            'priority' => ['sometimes', 'required', Rule::in(Issue::PRIORITIES)],
            'status' => ['sometimes', 'required', Rule::in(Issue::STATUSES)],
            'labels' => 'sometimes|nullable|string|max:700',
        ], self::VALIDATION_MESSAGES, self::VALIDATION_ATTRIBUTES);

        if (array_key_exists('labels', $data)) {
            $data['labels'] = $this->validatedLabels($data['labels']);
            if ($data['labels'] instanceof RedirectResponse) {
                return $data['labels'];
            }
        }

        return $this->attempt(
            fn () => $this->issues->update($issue, $request->user(), $data),
            fn () => redirect()->route('issues.show', [$repository, $issue->number])->with('status', 'Issue actualizada.'),
        );
    }

    public function destroy(Request $request, Repository $repository, int $number): RedirectResponse
    {
        $issue = $this->findIssue($repository, $number);
        Gate::authorize('delete', $issue);

        return $this->attempt(
            fn () => $this->issues->delete($issue, $request->user()),
            fn () => redirect()->route('issues.index', $repository)->with('status', "Issue #{$number} eliminada."),
        );
    }

    public function comment(Request $request, Repository $repository, int $number): RedirectResponse
    {
        $issue = $this->findIssue($repository, $number);
        Gate::authorize('comment', $issue);

        $data = $request->validate(['body' => 'required|string|max:10000'], self::VALIDATION_MESSAGES, self::VALIDATION_ATTRIBUTES);

        return $this->attempt(
            fn () => $this->issues->comment($issue, $request->user(), $data['body']),
            fn ($comment) => redirect()
                ->to(route('issues.show', [$repository, $issue->number]).'#comment-'.$comment->id)
                ->with('status', 'Comentario publicado.'),
        );
    }

    private function findIssue(Repository $repository, int $number): Issue
    {
        return Issue::where('repository_id', $repository->id)->where('number', $number)->firstOrFail();
    }

    /** @return array<int,string> id => nombre de los miembros del proyecto */
    private function memberOptions(Repository $repository): array
    {
        return $repository->project->members()->orderBy('name')->pluck('users.name', 'users.id')->all();
    }

    /** Valida la lista de etiquetas escrita separada por comas (máx. 20, de 30 caracteres). */
    private function validatedLabels(?string $input): array|RedirectResponse
    {
        $labels = $this->parseLabels($input);

        if (count($labels) > 20) {
            return back()->withInput()->withErrors(['labels' => 'Como máximo 20 etiquetas.']);
        }
        foreach ($labels as $label) {
            if (mb_strlen($label) > 30) {
                return back()->withInput()->withErrors(['labels' => "La etiqueta «{$label}» supera los 30 caracteres."]);
            }
        }

        return $labels;
    }

    /**
     * Comentarios + eventos del historial (cambios de estado, asignación, etiquetas...)
     * ordenados por fecha. Los eventos de creación y de comentario ya se ven de otra forma.
     *
     * @return Collection<int,array{type:string,at:\Illuminate\Support\Carbon,item:mixed}>
     */
    private function timeline(Issue $issue): Collection
    {
        $comments = $issue->comments->map(fn ($c) => ['type' => 'comment', 'at' => $c->created_at, 'order' => 1, 'id' => $c->id, 'item' => $c]);

        $events = $issue->activity
            ->where('action', 'issue.updated')
            ->map(fn ($log) => ['type' => 'event', 'at' => $log->created_at, 'order' => 0, 'id' => $log->id, 'item' => $log]);

        return $comments->concat($events)
            ->sortBy([fn ($a, $b) => $a['at'] <=> $b['at'], fn ($a, $b) => [$a['order'], $a['id']] <=> [$b['order'], $b['id']]])
            ->values();
    }

    /** Usuarios citados por id en el historial (asignaciones), para mostrar su nombre. */
    private function usersInHistory(Issue $issue): Collection
    {
        $ids = $issue->activity->flatMap(function ($log) {
            $change = $log->properties['changes']['assignee_id'] ?? null;

            return $change ? [$change['from'] ?? null, $change['to'] ?? null] : [];
        })->filter()->unique()->values();

        return $ids->isEmpty() ? collect() : User::whereIn('id', $ids)->pluck('name', 'id');
    }
}
