<?php

namespace App\Http\Controllers\Web\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Account\Presenters\ActivityPresenter;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

/**
 * Versión móvil de la web: solo consulta (actividad, notificaciones, proyectos y cuenta).
 * Sin código, subidas ni revisiones: eso se hace desde la versión completa.
 */
class MobileController extends Controller
{
    /** Cookie que recuerda que el usuario prefiere la versión completa en el móvil. */
    public const FULL_VERSION_COOKIE = 'codeflow_full';

    private const PER_PAGE = 20;

    /** Detección sencilla de teléfonos (las tabletas usan la versión completa). */
    public static function isPhone(Request $request): bool
    {
        return (bool) preg_match('/iPhone|iPod|Android.+Mobile|Mobile.+Firefox|Windows Phone/i', (string) $request->userAgent());
    }

    /** Actividad de mis proyectos (todos o uno). */
    public function activity(Request $request, ActivityPresenter $presenter): View
    {
        $projects = $this->myProjects($request)->pluck('name', 'id');
        $filter = ctype_digit((string) $request->query('project')) && $projects->has((int) $request->query('project'))
            ? (int) $request->query('project') : null;

        $logs = ActivityPresenter::eagerLoad(ActivityLog::query())
            ->whereIn('project_id', $filter ? [$filter] : $projects->keys())
            ->latest('id')
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();

        return view('mobile.activity', [
            'items' => collect($logs->items())->map(fn (ActivityLog $log) => $presenter->presentForApi($log, $projects[$log->project_id] ?? null)),
            'logs' => $logs,
            'projects' => $projects,
            'filter' => $filter,
        ]);
    }

    public function notifications(Request $request): View
    {
        $unreadOnly = $request->query('filter') === 'unread';

        $notifications = $request->user()->notifications()
            ->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();

        return view('mobile.notifications', ['notifications' => $notifications, 'unreadOnly' => $unreadOnly]);
    }

    /** En el móvil, abrir una notificación solo la marca como leída (no lleva a pantallas de código). */
    public function readNotification(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return back(fallback: route('mobile.notifications'));
    }

    public function readAllNotifications(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back(fallback: route('mobile.notifications'));
    }

    public function projects(Request $request): View
    {
        $roles = ProjectMember::where('user_id', $request->user()->id)->pluck('role', 'project_id');

        $projects = $this->myProjects($request)
            ->withCount(['repositories', 'members'])
            ->orderByDesc('updated_at')
            ->get()
            ->each(fn (Project $project) => $project->setAttribute('my_role', $roles[$project->id] ?? null));

        return view('mobile.projects', ['projects' => $projects]);
    }

    public function account(Request $request): View
    {
        return view('mobile.account', [
            'user' => $request->user(),
            'projectsCount' => $this->myProjects($request)->count(),
        ]);
    }

    /** "Ver versión completa": la portada deja de redirigir a la versión móvil en este navegador. */
    public function useFullVersion(): RedirectResponse
    {
        Cookie::queue(self::FULL_VERSION_COOKIE, '1', 60 * 24 * 365);

        return redirect()->route('home', ['completa' => 1]);
    }

    /** "Ver versión móvil" desde la versión completa. */
    public function useMobileVersion(): RedirectResponse
    {
        Cookie::queue(Cookie::forget(self::FULL_VERSION_COOKIE));

        return redirect()->route('mobile.activity');
    }

    /** Proyectos de los que soy miembro (también para el admin: en el móvil solo "los míos"). */
    private function myProjects(Request $request)
    {
        return Project::query()->whereIn('id', ProjectMember::where('user_id', $request->user()->id)->select('project_id'));
    }
}
