<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Mobile\MobileController;
use App\Models\Commit;
use App\Models\Issue;
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

    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return view('home.landing', [
                'publicRepositories' => Repository::query()->forListing()->with('project:id,name')
                    ->where('visibility', Repository::VISIBILITY_PUBLIC)
                    ->orderByDesc('last_commit_at')->orderByDesc('updated_at')
                    ->limit(6)->get(),
                'publicTotal' => Repository::where('visibility', Repository::VISIBILITY_PUBLIC)->count(),
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
        $projectIds = ProjectMember::where('user_id', $user->id)->pluck('project_id');
        $repositoryIds = Repository::select('id')->whereIn('project_id', $projectIds);

        return view('home.dashboard', [
            'stats' => $this->stats($user->id, $projectIds->all(), $repositoryIds),
            'projectsTotal' => $projectIds->count(),
            'repositories' => $this->recentRepositories($projectIds->all()),
        ]);
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
