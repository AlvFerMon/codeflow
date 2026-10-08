<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticación opcional para las rutas de lectura públicas.
 *
 * Si la petición trae un token Bearer válido, identifica al usuario; si no,
 * continúa como invitado ($request->user() === null) y decide la Policy.
 */
class OptionalSanctum
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
