<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Account\Presenters\ActivityPresenter;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Portada: página de bienvenida para invitados y panel personal para usuarios con sesión. */
class HomeController extends Controller
{
    public function __invoke(Request $request, ActivityPresenter $presenter): View
    {
        $user = $request->user();

        if (! $user) {
            return view('home.landing');
        }

        // "Mis proyectos": solo aquellos de los que soy miembro (también para el admin)
        $roles = ProjectMember::where('user_id', $user->id)->pluck('role', 'project_id');

        $projects = Project::query()
            ->whereIn('id', $roles->keys())
            ->withCount('repositories')
            ->latest('updated_at')
            ->get()
            ->each(fn (Project $project) => $project->setAttribute('my_role', $roles[$project->id] ?? null));

        $activity = ActivityPresenter::eagerLoad(ActivityLog::query())
            ->whereIn('project_id', $roles->keys())
            ->latest('id')
            ->limit(10)
            ->get();

        return view('home.dashboard', [
            'projects' => $projects,
            'notifications' => $user->notifications()->limit(5)->get(),
            'activity' => $presenter->presentMany($activity),
            'projectNames' => $projects->pluck('name', 'id'),
        ]);
    }
}
