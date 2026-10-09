<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En la web, un usuario con sesión y sin email verificado solo puede ver la pantalla de
 * verificación (y cerrar sesión). Los visitantes sin sesión navegan como siempre.
 */
class RedirectUnverifiedUsers
{
    private const ALLOWED = ['verification.notice', 'verification.verify', 'verification.send', 'verification.email', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail() && ! $request->routeIs(...self::ALLOWED)) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
