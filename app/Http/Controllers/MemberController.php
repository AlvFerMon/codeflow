<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorizeRole($request, $project, Roles::OBSERVER);

        return response()->json(
            ProjectMember::where('project_id', $project->id)->with('user:id,name,email')->get()
        );
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorizeRole($request, $project, Roles::OWNER);

        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => ['required', Rule::in(Roles::ASSIGNABLE)],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();

        if (ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->exists()) {
            abort(422, 'El usuario ya es miembro del proyecto.');
        }

        $member = ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id, 'role' => $data['role']]);
        Activity::log($project, $request->user(), 'member.added', $project, ['user' => $user->name, 'role' => $data['role']]);

        return response()->json($member->load('user:id,name,email'), 201);
    }

    public function update(Request $request, Project $project, User $user): JsonResponse
    {
        $this->authorizeRole($request, $project, Roles::OWNER);

        $data = $request->validate(['role' => ['required', Rule::in(Roles::ASSIGNABLE)]]);

        abort_if($user->id === $project->owner_id, 422, 'No se puede cambiar el rol del propietario.');

        $member = ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->firstOrFail();
        $member->update($data);

        return response()->json($member->load('user:id,name,email'));
    }

    public function destroy(Request $request, Project $project, User $user): JsonResponse
    {
        $this->authorizeRole($request, $project, Roles::OWNER);

        abort_if($user->id === $project->owner_id, 422, 'No se puede expulsar al propietario.');

        ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->delete();

        return response()->json(null, 204);
    }
}
