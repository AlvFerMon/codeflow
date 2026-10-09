<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Verificación del email con un código de 6 dígitos. Mientras no verifique, el usuario con
 * sesión solo puede estar en esta pantalla (RedirectUnverifiedUsers).
 */
class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationService $verification) {}

    /** Pantalla «Revisa tu correo» con el campo para el código. */
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        return view('auth.verify-email', ['user' => $request->user()]);
    }

    /** Comprueba el código introducido. */
    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|max:20'], ['code.required' => 'Escribe el código que te hemos enviado.']);

        $this->verification->verify($request->user(), $data['code']);

        return redirect()->route('home')->with('status', '¡Email verificado! Ya tienes acceso completo a CodeFlow.');
    }

    /** Envía un código nuevo (limitado por throttle en la ruta). */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $this->verification->send($request->user());

        return back()->with('status', 'Te hemos enviado un código nuevo a '.$request->user()->email.'.');
    }

    /** Corrige el email (si se escribió mal al registrarse) y envía el código a la dirección nueva. */
    public function changeEmail(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], ['email.unique' => 'Ya existe otra cuenta con ese correo.']);

        if ($data['email'] !== $user->email) {
            $user->update(['email' => $data['email']]); // el modelo envía el código a la dirección nueva
        }

        return back()->with('status', 'Email actualizado. Te hemos enviado el código a '.$data['email'].'.');
    }
}
