<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\Activity;
use App\Support\Roles;

class MembershipService
{
    public function __construct(private NotificationService $notifications) {}

    /** Da acceso a un usuario registrado (por email) con rol de colaborador u observador. */
    public function add(Project $project, User $actor, string $email, string $role): ProjectMember
    {
        $this->ensureAssignable($role);

        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->first();
        abort_unless($user, 422, 'No existe ningún usuario con ese correo.');

        if (ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->exists()) {
            abort(422, 'El usuario ya es miembro del proyecto.');
        }

        $member = ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id, 'role' => $role]);
        $user->forgetProjectRoles();

        Activity::log($project, $actor, 'member.added', $project, ['user_id' => $user->id, 'user' => $user->name, 'role' => $role]);

        $this->notifications->notifyUser(
            $user, $project, $actor, 'member.added',
            "{$actor->name} te ha dado acceso al proyecto {$project->name} como ".Roles::label($role),
            $project,
        );

        return $member;
    }

    public function changeRole(Project $project, User $actor, User $member, string $role): ProjectMember
    {
        $this->ensureAssignable($role);
        $this->ensureNotOwner($project, $member, 'No se puede cambiar el rol del propietario del proyecto.');

        $membership = ProjectMember::where('project_id', $project->id)->where('user_id', $member->id)->firstOrFail();
        $previous = $membership->role;

        if ($previous !== $role) {
            $membership->update(['role' => $role]);
            $member->forgetProjectRoles();

            Activity::log($project, $actor, 'member.role_changed', $project, [
                'user_id' => $member->id, 'user' => $member->name, 'from' => $previous, 'to' => $role,
            ]);

            $this->notifications->notifyUser(
                $member, $project, $actor, 'member.role_changed',
                "{$actor->name} ha cambiado tu rol en el proyecto {$project->name} a ".Roles::label($role),
                $project,
            );
        }

        return $membership;
    }

    public function remove(Project $project, User $actor, User $member): void
    {
        $this->ensureNotOwner($project, $member, 'No se puede expulsar al propietario del proyecto.');

        $deleted = ProjectMember::where('project_id', $project->id)->where('user_id', $member->id)->delete();
        $member->forgetProjectRoles();

        if ($deleted) {
            Activity::log($project, $actor, 'member.removed', $project, ['user_id' => $member->id, 'user' => $member->name]);
        }
    }

    private function ensureAssignable(string $role): void
    {
        abort_unless(in_array($role, Roles::ASSIGNABLE, true), 422, 'Rol no válido: el propietario es único.');
    }

    private function ensureNotOwner(Project $project, User $member, string $message): void
    {
        $isOwner = $member->id === $project->owner_id
            || ProjectMember::where('project_id', $project->id)->where('user_id', $member->id)->value('role') === Roles::OWNER;

        abort_if($isOwner, 422, $message);
    }
}
