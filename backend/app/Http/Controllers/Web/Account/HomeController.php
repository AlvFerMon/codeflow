<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Mobile\MobileController;
use App\Models\Commit;
use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\PullRequest;
use App\Models\Repository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portada: página de bienvenida para invitados y panel personal ("Flujo de trabajo") para usuarios con sesión:
 * métricas y repositorios recientes de los proyectos de los que el usuario es miembro. La actividad y las
 * notificaciones se consultan desde la cabecera.
 */
class HomeController extends Controller
{
    private const RECENT_REPOSITORIES = 5;

    private const STAMPS_PER_REPOSITORY = 4;

    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            $publicRepositories = Repository::query()->forListing()->with('project:id,name')
                ->where('visibility', Repository::VISIBILITY_PUBLIC)
                ->orderByDesc('last_commit_at')->orderByDesc('updated_at')
                ->limit(6)->get();
            $featured = $publicRepositories->first();

            return view('home.landing', [
                'publicRepositories' => $publicRepositories,
                'publicTotal' => Repository::where('visibility', Repository::VISIBILITY_PUBLIC)->count(),
                // La ficha de ejemplo de la portada: el repositorio público más reciente con sus sellos
                'featured' => $featured,
                'featuredStamps' => $featured ? $this->latestCommits([$featured->id])->get($featured->id, collect()) : collect(),
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // En un teléfono se abre la versión móvil (solo consulta), salvo que se haya elegido la completa
        if (MobileController::isPhone($request) && ! $request->cookie(MobileController::FULL_VERSION_COOKIE) && ! $request->has('completa')) {
            return redirect()->route('mobile.activity');
        }

        // "Mis proyectos": solo aquellos de los que soy miembro (también para el admin)
        $projectIds = Project::whereIn('id', ProjectMember::where('user_id', $user->id)->select('project_id'))->pluck('id'); // sin los de la papelera
        $repositoryIds = Repository::select('id')->whereIn('project_id', $projectIds);

        $repositories = $this->recentRepositories($projectIds->all());

        return view('home.dashboard', [
            'stats' => $this->stats($user->id, $projectIds->all(), $repositoryIds),
            'projectsTotal' => $projectIds->count(),
            'repositories' => $repositories,
            'stamps' => $this->latestCommits($repositories->pluck('id')->all()),
            'todo' => $this->todo($user->id, $repositoryIds),
        ]);
    }

    /** Últimos commits de cada repositorio reciente: la columna de sellos de su ficha. */
    private function latestCommits(array $repositoryIds)
    {
        if ($repositoryIds === []) {
            return collect();
        }

        return Commit::query()
            ->select(['id', 'repository_id', 'author_id', 'sha', 'message', 'created_at'])
            ->with('author:id,name')
            ->whereIn('repository_id', $repositoryIds)
            ->latest('created_at')
            ->limit(self::STAMPS_PER_REPOSITORY * count($repositoryIds) * 4)
            ->get()
            ->groupBy('repository_id')
            ->map(fn ($commits) => $commits->take(self::STAMPS_PER_REPOSITORY));
    }

    /**
     * «Te toca»: revisiones que me piden, issues asignadas a mí o urgentes y lo fusionado esta semana.
     * Cada elemento lleva el estado que decide la forma de su sello.
     */
    private function todo(int $userId, $repositoryIds)
    {
        $reviews = PullRequest::with('repository:id,name', 'author:id,name')
            ->whereIn('repository_id', $repositoryIds)
            ->whereIn('status', ['open', 'in_review'])
            ->whereHas('reviewers', fn ($q) => $q->where('users.id', $userId))
            ->latest('updated_at')->limit(4)->get()
            ->map(fn (PullRequest $pr) => ['kind' => 'pr', 'state' => 'pending', 'model' => $pr,
                'note' => ($pr->author?->name ?? 'Alguien').' pide tu revisión']);

        $mine = PullRequest::with('repository:id,name')
            ->whereIn('repository_id', $repositoryIds)
            ->where('author_id', $userId)
            ->whereIn('status', ['approved', 'changes_requested'])
            ->latest('updated_at')->limit(3)->get()
            ->map(fn (PullRequest $pr) => ['kind' => 'pr', 'state' => 'pending', 'model' => $pr,
                'note' => $pr->status === 'approved' ? 'Aprobada: ya puedes fusionarla' : 'Te piden cambios']);

        $issues = Issue::with('repository:id,name')
            ->whereIn('repository_id', $repositoryIds)
            ->whereIn('status', ['open', 'in_progress'])
            ->where(fn ($q) => $q->where('assignee_id', $userId)->orWhereIn('priority', ['high', 'critical']))
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 ELSE 2 END")
            ->latest('updated_at')->limit(4)->get()
            ->map(fn (Issue $issue) => ['kind' => 'issue', 'state' => 'pending', 'model' => $issue,
                'note' => match ($issue->priority) {
                    'critical' => 'Prioridad crítica',
                    'high' => 'Prioridad alta',
                    default => 'Asignada a ti',
                }]);

        $merged = PullRequest::with('repository:id,name')
            ->whereIn('repository_id', $repositoryIds)
            ->where('status', 'merged')
            ->where('merged_at', '>=', now()->subDays(7)) // fecha de fusión (un comentario posterior no la cambia)
            ->latest('merged_at')->limit(2)->get()
            ->map(fn (PullRequest $pr) => ['kind' => 'pr', 'state' => 'done', 'model' => $pr,
                'note' => 'Fusionada '.($pr->merged_at ?? $pr->updated_at)->diffForHumans()]);

        return $reviews->concat($mine)->concat($issues)->concat($merged)->values();
    }

    /** Cifras de las tarjetas KPI (consultas agregadas, sin cargar modelos). */
    private function stats(int $userId, array $projectIds, $repositoryIds): array
    {
        $visibility = Repository::whereIn('project_id', $projectIds)
            ->selectRaw('visibility, count(*) as total')
            ->groupBy('visibility')
            ->pluck('total', 'visibility');

        $pulls = PullRequest::whereIn('repository_id', $repositoryIds)
            ->whereIn('status', PullRequest::OPEN_STATES)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $openIssues = Issue::whereIn('repository_id', $repositoryIds)
            ->whereIn('status', ['open', 'in_progress'])
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $weekAgo = now()->subDays(7);

        return [
            'repositories' => (int) $visibility->sum(),
            'public' => (int) ($visibility['public'] ?? 0),
            'private' => (int) ($visibility['private'] ?? 0),
            'pulls' => (int) $pulls->sum(),
            'pulls_approved' => (int) ($pulls['approved'] ?? 0),
            'pulls_to_review' => PullRequest::whereIn('repository_id', $repositoryIds)
                ->whereIn('status', ['open', 'in_review'])
                ->whereHas('reviewers', fn ($q) => $q->where('users.id', $userId))
                ->count(),
            'issues' => (int) $openIssues->sum(),
            'issues_urgent' => (int) (($openIssues['high'] ?? 0) + ($openIssues['critical'] ?? 0)),
            'issues_resolved_week' => Issue::whereIn('repository_id', $repositoryIds)
                ->whereIn('status', ['resolved', 'closed'])
                ->where('updated_at', '>=', $weekAgo)
                ->count(),
            'commits_week' => Commit::whereIn('repository_id', $repositoryIds)->where('created_at', '>=', $weekAgo)->count(),
            'commits_today' => Commit::whereIn('repository_id', $repositoryIds)->where('created_at', '>=', now()->startOfDay())->count(),
        ];
    }

    /** Repositorios ordenados por su último commit, con el sha y la fecha de ese commit. */
    private function recentRepositories(array $projectIds)
    {
        return Repository::query()
            ->forListing()
            ->with('project:id,name')
            ->whereIn('project_id', $projectIds)
            ->orderByDesc('last_commit_at')
            ->orderByDesc('updated_at')
            ->limit(self::RECENT_REPOSITORIES)
            ->get();
    }
}
