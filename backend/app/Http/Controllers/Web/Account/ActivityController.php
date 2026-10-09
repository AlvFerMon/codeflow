<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Account\Presenters\ActivityPresenter;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Historial de actividad (30 por página): de un proyecto o de todos los proyectos del usuario. */
class ActivityController extends Controller
{
    public function __invoke(Project $project, ActivityPresenter $presenter): View
    {
        Gate::authorize('view', $project);

        $logs = ActivityPresenter::eagerLoad(ActivityLog::query())
            ->where('project_id', $project->id)
            ->latest('id')
            ->paginate(30);

        return view('activity.index', [
            'project' => $project,
            'logs' => $logs,
            'items' => $presenter->presentMany($logs->items()),
        ]);
    }

    /** Actividad de todos mis proyectos, con filtro opcional por proyecto. */
    public function global(Request $request, ActivityPresenter $presenter): View
    {
        $projects = Project::whereIn('id', ProjectMember::where('user_id', $request->user()->id)->select('project_id'))
            ->orderBy('name')->pluck('name', 'id');
        $filter = ctype_digit((string) $request->query('project')) && $projects->has((int) $request->query('project'))
            ? (int) $request->query('project') : null;

        $logs = ActivityPresenter::eagerLoad(ActivityLog::query())
            ->whereIn('project_id', $filter ? [$filter] : $projects->keys())
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('activity.global', [
            'logs' => $logs,
            'items' => $presenter->presentMany($logs->items()),
            'projects' => $projects,
            'filter' => $filter,
        ]);
    }
}
