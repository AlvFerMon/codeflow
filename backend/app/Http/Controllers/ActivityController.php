<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Web\Account\Presenters\ActivityPresenter;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Actividad para la API (app Android): quién hizo qué, en qué repositorio y rama, y cuándo.
 * Paginado (30) del evento más reciente al más antiguo.
 */
class ActivityController extends Controller
{
    /** GET /api/projects/{project}/activity (?action=commit para filtrar por tipo). */
    public function index(Request $request, Project $project, ActivityPresenter $presenter)
    {
        Gate::authorize('view', $project);

        return ActivityPresenter::eagerLoad(ActivityLog::query())
            ->where('project_id', $project->id)
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', 'like', $action.'%'))
            ->latest('id')
            ->paginate(30)
            ->through(fn (ActivityLog $log) => $presenter->presentForApi($log, $project->name));
    }

    /** GET /api/me/activity: actividad de todos mis proyectos (?project_id= para uno solo). */
    public function mine(Request $request, ActivityPresenter $presenter)
    {
        $projects = Project::whereIn('id', ProjectMember::where('user_id', $request->user()->id)->select('project_id'))
            ->pluck('name', 'id');
        $projectId = (int) $request->query('project_id');
        $ids = $projectId && $projects->has($projectId) ? [$projectId] : $projects->keys()->all();

        return ActivityPresenter::eagerLoad(ActivityLog::query())
            ->whereIn('project_id', $ids ?: [0])
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', 'like', $action.'%'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (ActivityLog $log) => $presenter->presentForApi($log, $projects[$log->project_id] ?? null));
    }
}
