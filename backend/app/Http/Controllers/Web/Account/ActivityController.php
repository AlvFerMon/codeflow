<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Account\Presenters\ActivityPresenter;
use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Historial de actividad del proyecto (30 por página), visible para todos los miembros. */
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
}
