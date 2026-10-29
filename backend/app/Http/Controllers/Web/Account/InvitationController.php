<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Enlace del correo de invitación. Las invitaciones se aceptan solas al verificar el correo o
 * iniciar sesión con él; este enlace solo guía a la persona al paso que le falta.
 */
class InvitationController extends Controller
{
    public function __invoke(Request $request, string $token, InvitationService $invitations): RedirectResponse
    {
        $invitation = $invitations->findByToken($token);

        if (! $invitation || $invitation->accepted_at || $invitation->expires_at->isPast()) {
            return redirect()->route($request->user() ? 'home' : 'login')
                ->with('error', 'La invitación no existe, ya se usó o ha caducado. Pide a quien te invitó que te la envíe de nuevo.');
        }

        $project = $invitation->project->name;
        $user = $request->user();

        if (! $user) {
            $hasAccount = User::whereRaw('LOWER(email) = ?', [$invitation->email])->exists();

            return redirect()->route($hasAccount ? 'login' : 'register', ['email' => $invitation->email])
                ->with('status', $hasAccount
                    ? "Inicia sesión para unirte al proyecto «{$project}»."
                    : "Crea tu cuenta con {$invitation->email} para unirte al proyecto «{$project}».");
        }

        if (mb_strtolower($user->email) !== $invitation->email) {
            return redirect()->route('home')->with('error', "Esta invitación es para {$invitation->email}. Cierra sesión y entra con esa cuenta.");
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('status', "Verifica tu correo para unirte al proyecto «{$project}».");
        }

        $invitations->acceptPendingFor($user);

        return redirect()->route('projects.show', $invitation->project)->with('status', "Ya eres miembro del proyecto «{$project}».");
    }
}
