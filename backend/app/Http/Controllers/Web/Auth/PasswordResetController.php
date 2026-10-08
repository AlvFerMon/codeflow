<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Recuperación de contraseña con el broker de Laravel (enlace por correo con token). */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        // Respuesta neutra para no revelar qué correos existen
        return back()->with('status', 'Si el correo existe, recibirás un enlace para restablecer la contraseña.');
    }

    /** Compatibilidad con enlaces del tipo /reset-password?token=...&email=... */
    public function redirectToForm(Request $request): RedirectResponse
    {
        $token = (string) $request->query('token', '');

        if ($token === '') {
            return redirect()->route('password.request');
        }

        return redirect()->route('password.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));
                $user->save();
                // Igual que la API: se cierran las sesiones de la app Android
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => match ($status) {
                Password::RESET_THROTTLED => 'Espera un momento antes de volver a intentarlo.',
                default => 'El enlace no es válido o ha caducado. Solicita uno nuevo.',
            }]);
        }

        return redirect()->route('login')->with('status', 'Contraseña restablecida. Ya puedes iniciar sesión.');
    }
}
