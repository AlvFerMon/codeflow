<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\MembershipService;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Miembros del proyecto: cualquiera puede verlos; solo el propietario (o el admin) los gestiona. */
class MemberController extends Controller
{
    public function __construct(private MembershipService $memberships, private InvitationService $invitations) {}

    public function index(Request $request, Project $project): View
    {
        Gate::authorize('view', $project);

        $q = is_string($request->query('q')) ? trim(mb_substr($request->query('q'), 0, 100)) : '';

        // 25 por página, por rol (propietario, colaboradores, observadores) y nombre; búsqueda por nombre o email
        $members = ProjectMember::query()
            ->select('project_members.*')
            ->join('users', 'users.id', '=', 'project_members.user_id')
            ->where('project_members.project_id', $project->id)
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('users.name', 'like', "%{$q}%")->orWhere('users.email', 'like', "%{$q}%")))
            ->orderByRaw("CASE project_members.role WHEN 'owner' THEN 0 WHEN 'collaborator' THEN 1 ELSE 2 END")
            ->orderBy('users.name')
            ->with('user:id,name,email')
            ->paginate(25)
            ->withQueryString();

        return view('members.index', [
            'project' => $project,
            'members' => $members,
            'q' => $q,
            'roleOptions' => self::roleOptions(),
            'invitations' => Gate::allows('manageMembers', $project)
                ? ProjectInvitation::pending()->where('project_id', $project->id)->with('inviter:id,name')->latest()->get()
                : collect(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('manageMembers', $project);

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'role' => ['required', Rule::in(Roles::ASSIGNABLE)],
        ], [
            'email.required' => 'Indica el correo del usuario.',
            'email.email' => 'El correo no es válido.',
            'role.in' => 'Rol no válido: el propietario es único.',
        ]);

        // Sin cuenta en CodeFlow: se le invita por correo
        if (! User::whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])->exists()) {
            try {
                $this->invitations->invite($project, $request->user(), $data['email'], $data['role']);
            } catch (HttpException $e) {
                return back()->withInput()->withErrors(['email' => $e->getMessage()]);
            }

            return back()->with('status', "Hemos enviado una invitación a {$data['email']}. Entrará al proyecto cuando cree su cuenta y verifique el correo.");
        }

        try {
            $member = $this->memberships->add($project, $request->user(), $data['email'], $data['role']);
        } catch (HttpException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return back()->with('status', "{$member->user->name} ahora es ".Roles::label($member->role).' del proyecto.');
    }

    public function update(Request $request, Project $project, User $user): RedirectResponse
    {
        Gate::authorize('manageMembers', $project);

        $data = $request->validate(['role' => ['required', Rule::in(Roles::ASSIGNABLE)]], [
            'role.in' => 'Rol no válido: el propietario es único.',
        ]);

        try {
            $this->memberships->changeRole($project, $request->user(), $user, $data['role']);
        } catch (HttpException $e) {
            return back()->with('error', $e->getMessage() ?: 'No se ha podido cambiar el rol.');
        }

        return back()->with('status', "Rol de {$user->name} actualizado a ".Roles::label($data['role']).'.');
    }

    public function destroy(Request $request, Project $project, User $user): RedirectResponse
    {
        Gate::authorize('manageMembers', $project);

        try {
            $this->memberships->remove($project, $request->user(), $user);
        } catch (HttpException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "{$user->name} ya no tiene acceso al proyecto.");
    }

    public function cancelInvitation(Request $request, Project $project, ProjectInvitation $invitation): RedirectResponse
    {
        Gate::authorize('manageMembers', $project);
        abort_unless($invitation->project_id === $project->id, 404);

        try {
            $this->invitations->cancel($invitation, $request->user());
        } catch (HttpException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Invitación a {$invitation->email} cancelada.");
    }

    /** @return array<string,string> */
    public static function roleOptions(): array
    {
        return collect(Roles::ASSIGNABLE)->mapWithKeys(fn ($role) => [$role => ucfirst(Roles::label($role))])->all();
    }
}
