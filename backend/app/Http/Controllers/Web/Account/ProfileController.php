<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Models\ProjectMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Perfil del usuario: datos personales y cambio de contraseña. */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'memberships' => ProjectMember::where('user_id', $user->id)->with('project:id,name')->get()
                ->filter(fn (ProjectMember $m) => $m->project !== null)
                ->sortBy(fn (ProjectMember $m) => mb_strtolower($m->project->name)),
            'tokens' => $user->tokens()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Cambiar el correo pide la contraseña: con una sesión robada no se puede secuestrar la cuenta
        $emailChanged = strcasecmp(trim((string) $request->input('email')), $user->email) !== 0;
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'email_password' => [Rule::requiredIf($emailChanged), 'nullable', 'string', 'current_password'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.unique' => 'Ya existe otra cuenta con ese correo.',
            'email_password.required' => 'Para cambiar el correo escribe tu contraseña actual.',
            'email_password.current_password' => 'La contraseña no es correcta.',
        ]);
        unset($data['email_password']);
        if (! $emailChanged) {
            $data['email'] = $user->email; // mismo correo (quizá con otras mayúsculas): no hay que volver a verificarlo
        }

        $user->update($data);

        return redirect()->route('profile.edit')->with('status', 'Perfil actualizado.');
    }

    /**
     * Cambia la contraseña (pide la actual). Como en la API, se revocan los tokens de la app
     * Android; además se cierran las demás sesiones web y se invalida el "Recordarme".
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string|current_password',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.different' => 'La contraseña nueva debe ser distinta de la actual.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $data['password']])->setRememberToken(Str::random(60));
        $user->save();

        $user->tokens()->delete();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $request->session()->regenerate();

        return redirect()->route('profile.edit')->with('status', 'Contraseña actualizada. Se han cerrado las sesiones en otros dispositivos.');
    }
}
