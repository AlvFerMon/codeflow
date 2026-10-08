<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
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
    public function __construct(private MembershipService $memberships) {}

    public function index(Project $project): View
    {
        Gate::authorize('view', $project);

        $members = ProjectMember::where('project_id', $project->id)
            ->with('user:id,name,email')
            ->get()
            ->sortBy([
                fn ($a, $b) => Roles::level($b->role) <=> Roles::level($a->role),
                fn ($a, $b) => strcasecmp($a->user?->name ?? '', $b->user?->name ?? ''),
            ]);

        return view('members.index', [
            'project' => $project,
            'members' => $members,
            'roleOptions' => self::roleOptions(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('manageMembers', $project);

        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => ['required', Rule::in(Roles::ASSIGNABLE)],
        ], [
            'email.required' => 'Indica el correo del usuario.',
            'email.email' => 'El correo no es válido.',
            'email.exists' => 'No existe ningún usuario con ese correo.',
            'role.in' => 'Rol no válido: el propietario es único.',
        ]);

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

    /** @return array<string,string> */
    public static function roleOptions(): array
    {
        return collect(Roles::ASSIGNABLE)->mapWithKeys(fn ($role) => [$role => ucfirst(Roles::label($role))])->all();
    }
}
