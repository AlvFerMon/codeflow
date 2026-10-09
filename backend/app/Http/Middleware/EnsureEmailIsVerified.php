<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige email verificado. En la web redirige a la pantalla de aviso; en la API responde 403
 * con un código estable (email_not_verified) para que la app Android muestre su propia pantalla.
 */
class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Debes verificar tu email antes de continuar. Introduce el código que te hemos enviado o pide uno nuevo.',
                    'code' => 'email_not_verified',
                ], 403);
            }

            return redirect()->guest(route('verification.notice'));
        }

        return $next($request);
    }
}
