<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Support\Roles;
use Illuminate\Http\Request;

abstract class Controller
{
    /** Rol efectivo del usuario autenticado en un proyecto (el admin global actúa como propietario). */
    protected function roleIn(Request $request, Project $project): ?string
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return Roles::OWNER;
        }

        return ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->value('role');
    }

    /** Aborta con 403 si el usuario no alcanza el rol mínimo. Devuelve su rol. */
    protected function authorizeRole(Request $request, Project $project, string $minimum): string
    {
        $role = $this->roleIn($request, $project);

        if (Roles::level($role) < Roles::level($minimum)) {
            abort(403, 'No tienes permisos suficientes en este proyecto.');
        }

        return $role;
    }

    protected function ensureMember(Project $project, int $userId): void
    {
        $isMember = ProjectMember::where('project_id', $project->id)->where('user_id', $userId)->exists();

        abort_unless($isMember, 422, 'El usuario indicado no pertenece al proyecto.');
    }
}
