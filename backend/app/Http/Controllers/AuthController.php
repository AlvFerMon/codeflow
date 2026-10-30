<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\InvitationService;
use App\Support\Preferences;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // se cifra con el cast "hashed"
        ]);

        // Envía el correo de verificación (listener SendEmailVerificationNotification)
        event(new Registered($user));

        return response()->json([
            'user' => $user,
            'token' => $user->createToken($data['device_name'] ?? 'web')->plainTextToken,
            'message' => 'Cuenta creada. Te hemos enviado un código para verificar tu email.',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $data['email'])->first();

        // Mismo mensaje genérico para credenciales erróneas y cuentas desactivadas
        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->isActive()) {
            throw ValidationException::withMessages(['email' => ['Credenciales incorrectas.']]);
        }

        // Invitaciones pendientes a su correo (si alguien le invitó antes de tener cuenta)
        app(InvitationService::class)->acceptPendingFor($user);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken($data['device_name'] ?? 'web')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /** Verifica el email con el código de 6 dígitos recibido por correo. */
    public function verifyEmail(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string|max:20']);

        $verification->verify($request->user(), $data['code']);

        return response()->json(['message' => 'Email verificado.', 'user' => $request->user()->fresh()]);
    }

    /** Reenvía el código de verificación del email. */
    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Tu email ya está verificado.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Te hemos enviado un código nuevo a '.$user->email.'.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Las preferencias solo se devuelven aquí (no en los listados donde aparecen otros usuarios)
        return response()->json([...$user->toArray(), 'preferences' => Preferences::for($user)]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => 'required_with:password|string',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        // Cambiar el correo también pide la contraseña actual (un token robado no basta para quedarse la cuenta)
        $emailChanged = isset($data['email']) && strcasecmp($data['email'], $user->email) !== 0;
        if ($emailChanged && ! Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['Para cambiar el correo escribe tu contraseña actual.']]);
        }
        if (isset($data['email']) && ! $emailChanged) {
            unset($data['email']);
        }

        if (isset($data['password']) && ! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['La contraseña actual no es correcta.']]);
        }

        unset($data['current_password']);
        $user->update($data);

        if (isset($data['password'])) {
            $this->revokeOtherTokens($request);
        }

        return response()->json($user->fresh());
    }

    /** Cambia la contraseña (pide la actual) y cierra la sesión en los demás dispositivos. */
    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['La contraseña actual no es correcta.']]);
        }

        $user->update(['password' => $data['password']]);
        $this->revokeOtherTokens($request);

        return response()->json(['message' => 'Contraseña actualizada. Se han cerrado las sesiones en otros dispositivos.']);
    }

    /** Revoca todos los tokens del usuario salvo el de la petición actual. */
    private function revokeOtherTokens(Request $request): void
    {
        $user = $request->user();
        $current = $user->currentAccessToken();

        $user->tokens()
            ->when($current instanceof PersonalAccessToken, fn ($q) => $q->whereKeyNot($current->getKey()))
            ->delete();
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        // Respuesta neutra para no revelar qué correos existen
        return response()->json(['message' => 'Si el correo existe, recibirás un enlace para restablecer la contraseña.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => 'Contraseña restablecida.']);
    }
}
