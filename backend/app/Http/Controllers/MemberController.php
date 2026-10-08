<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\MembershipService;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function __construct(private MembershipService $memberships) {}

    public function index(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return response()->json(
            ProjectMember::where('project_id', $project->id)->with('user:id,name,email')->get()
        );
    }

    /** Solo el propietario (o el admin) puede dar acceso y asignar roles. */
    public function store(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => ['required', Rule::in(Roles::ASSIGNABLE)],
        ]);

        $member = $this->memberships->add($project, $request->user(), $data['email'], $data['role']);

        return response()->json($member->load('user:id,name,email'), 201);
    }

    public function update(Request $request, Project $project, User $user): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        $data = $request->validate(['role' => ['required', Rule::in(Roles::ASSIGNABLE)]]);

        $member = $this->memberships->changeRole($project, $request->user(), $user, $data['role']);

        return response()->json($member->load('user:id,name,email'));
    }

    public function destroy(Request $request, Project $project, User $user): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        $this->memberships->remove($project, $request->user(), $user);

        return response()->json(null, 204);
    }
}
