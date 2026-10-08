<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActivityController extends Controller
{
    public function index(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        return ActivityLog::where('project_id', $project->id)
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', 'like', $action.'%'))
            ->with('user:id,name')
            ->latest('id')
            ->paginate(30);
    }
}
