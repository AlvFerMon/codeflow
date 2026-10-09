<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\VerificationCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Verificación del email con un código de 6 dígitos enviado por correo.
 * Solo se guarda el hash del código; caduca a los 30 minutos y admite 5 intentos.
 */
class EmailVerificationService
{
    public const MINUTES = 30;

    public const MAX_ATTEMPTS = 5;

    /** Genera un código nuevo (invalida el anterior) y lo envía por correo. */
    public function send(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_expires_at' => now()->addMinutes(self::MINUTES),
            'email_verification_attempts' => 0,
        ])->saveQuietly();

        $user->notify(new VerificationCode($code, self::MINUTES));
    }

    /** Comprueba el código; si es correcto marca el email como verificado. */
    public function verify(User $user, string $code): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $code = preg_replace('/\D/', '', $code);
        $fail = fn (string $message) => throw ValidationException::withMessages(['code' => $message]);

        if (! $user->email_verification_code || ! $user->email_verification_expires_at || $user->email_verification_expires_at->isPast()) {
            $fail('El código ha caducado. Pide uno nuevo con «Reenviar código».');
        }

        if ($user->email_verification_attempts >= self::MAX_ATTEMPTS) {
            $fail('Has superado el número de intentos. Pide un código nuevo con «Reenviar código».');
        }

        if (strlen($code) !== 6 || ! Hash::check($code, $user->email_verification_code)) {
            $user->forceFill(['email_verification_attempts' => $user->email_verification_attempts + 1])->saveQuietly();
            $left = self::MAX_ATTEMPTS - $user->email_verification_attempts;
            $fail($left > 0
                ? "El código no es correcto. Te quedan {$left} ".($left === 1 ? 'intento' : 'intentos').'.'
                : 'El código no es correcto. Pide uno nuevo con «Reenviar código».');
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
            'email_verification_attempts' => 0,
        ])->saveQuietly();

        event(new Verified($user));
    }
}
