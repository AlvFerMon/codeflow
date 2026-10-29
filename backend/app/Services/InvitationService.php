<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectMember;
use App\Models\User;
use App\Notifications\ProjectInvitationNotification;
use App\Support\Activity;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Invitaciones por correo a personas sin cuenta. Se aceptan solas cuando alguien verifica (o inicia
 * sesión con) ese correo, así que no hace falta conservar el enlace. El enlace solo lleva al registro.
 */
class InvitationService
{
    public const DAYS = 14;

    public function __construct(private NotificationService $notifications) {}

    /** Crea (o renueva) la invitación y envía el correo. */
    public function invite(Project $project, User $actor, string $email, string $role): ProjectInvitation
    {
        abort_unless(in_array($role, Roles::ASSIGNABLE, true), 422, 'Rol no válido: el propietario es único.');
        $email = mb_strtolower(trim($email));
        abort_if(User::whereRaw('LOWER(email) = ?', [$email])->exists(), 422, 'Ese correo ya tiene cuenta: añádelo directamente.');

        $token = Str::random(48);
        $invitation = ProjectInvitation::where('project_id', $project->id)->where('email', $email)->whereNull('accepted_at')->first()
            ?? new ProjectInvitation(['project_id' => $project->id, 'email' => $email]);

        $invitation->fill([
            'role' => $role,
            'token_hash' => ProjectInvitation::hashToken($token),
            'invited_by' => $actor->id,
            'expires_at' => now()->addDays(self::DAYS),
        ])->save();

        try {
            Notification::route('mail', $email)->notify(
                new ProjectInvitationNotification($invitation->load('project', 'inviter'), route('invitations.show', $token))
            );
        } catch (\Throwable $e) {
            report($e);
            $invitation->delete();
            abort(503, 'No se ha podido enviar el correo de invitación. Inténtalo de nuevo en un rato.');
        }

        Activity::log($project, $actor, 'member.invited', $project, ['email' => $email, 'role' => $role]);

        return $invitation;
    }

    public function cancel(ProjectInvitation $invitation, User $actor): void
    {
        abort_if($invitation->accepted_at, 422, 'La invitación ya se aceptó.');
        Activity::log($invitation->project, $actor, 'member.invitation_cancelled', $invitation->project, ['email' => $invitation->email]);
        $invitation->delete();
    }

    public function findByToken(string $token): ?ProjectInvitation
    {
        return ProjectInvitation::where('token_hash', ProjectInvitation::hashToken($token))->with('project', 'inviter')->first();
    }

    /** Acepta todas las invitaciones pendientes del correo del usuario (si lo tiene verificado). */
    public function acceptPendingFor(User $user): int
    {
        if (! $user->hasVerifiedEmail()) {
            return 0;
        }

        $accepted = 0;
        $pending = ProjectInvitation::pending()->where('email', mb_strtolower($user->email))->with('project', 'inviter')->get();

        foreach ($pending as $invitation) {
            DB::transaction(function () use ($invitation, $user, &$accepted) {
                $invitation->forceFill(['accepted_at' => now(), 'accepted_by' => $user->id])->save();

                if ($user->roleIn($invitation->project)) {
                    return; // ya era miembro
                }

                ProjectMember::create(['project_id' => $invitation->project_id, 'user_id' => $user->id, 'role' => $invitation->role]);
                $user->forgetProjectRoles();
                Activity::log($invitation->project, $user, 'member.joined', $invitation->project, ['role' => $invitation->role, 'via' => 'invitation']);
                $accepted++;

                if ($invitation->inviter) {
                    $this->notifications->notifyUser(
                        $invitation->inviter, $invitation->project, $user, 'member.joined',
                        "{$user->name} aceptó tu invitación y ya es ".Roles::label($invitation->role)." del proyecto {$invitation->project->name}",
                        $invitation->project,
                    );
                }
            });
        }

        return $accepted;
    }
}
