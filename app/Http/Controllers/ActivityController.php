<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Support\Roles;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorizeRole($request, $project, Roles::OBSERVER);

        return ActivityLog::where('project_id', $project->id)
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', 'like', $action.'%'))
            ->with('user:id,name')
            ->latest('id')
            ->paginate(30);
    }
}
