<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\MembershipService;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function __construct(private MembershipService $memberships, private InvitationService $invitations) {}

    public function index(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return response()->json(
            ProjectMember::where('project_id', $project->id)->with('user:id,name,email')->orderBy('id')->paginate(50)
        );
    }

    /** Solo el propietario (o el admin) puede dar acceso y asignar roles. */
    public function store(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'role' => ['required', Rule::in(Roles::ASSIGNABLE)],
        ]);

        // Sin cuenta: se le envía una invitación por correo (202 Accepted)
        if (! User::whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])->exists()) {
            $invitation = $this->invitations->invite($project, $request->user(), $data['email'], $data['role']);

            return response()->json(['invitation' => $invitation, 'message' => 'Invitación enviada por correo.'], 202);
        }

        $member = $this->memberships->add($project, $request->user(), $data['email'], $data['role']);

        return response()->json($member->load('user:id,name,email'), 201);
    }

    /** Invitaciones pendientes (sin aceptar y sin caducar). */
    public function invitations(Project $project): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        return response()->json(['data' => ProjectInvitation::pending()->where('project_id', $project->id)->with('inviter:id,name')->latest()->get()]);
    }

    public function cancelInvitation(Request $request, ProjectInvitation $invitation): JsonResponse
    {
        Gate::authorize('manageMembers', $invitation->project);
        $this->invitations->cancel($invitation, $request->user());

        return response()->json(null, 204);
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
