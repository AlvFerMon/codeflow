<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en todas las respuestas y filtro de parámetros raros.
 *
 * - Ninguna página se puede meter en un iframe de otra web (clickjacking).
 * - El navegador no adivina tipos de archivo (el «raw» de un repo nunca se ejecuta como HTML/JS).
 * - Al salir a otra web solo se envía el dominio, no la URL (que puede incluir rutas de repos privados).
 * - Sin cámara, micrófono ni ubicación.
 * - No se anuncia la versión de PHP.
 *
 * Ninguna ruta GET usa listas en la URL (?q[]=…): si llegan, se responde 400 en vez de fallar con un 500.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->hasArrayQuery($request)) {
            abort(400, 'Parámetros de la dirección no válidos.');
        }

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'");
        }
        $headers->remove('X-Powered-By');
        if (function_exists('header_remove') && ! app()->runningInConsole()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }

    private function hasArrayQuery(Request $request): bool
    {
        if (! $request->isMethodSafe()) {
            return false;
        }
        foreach ($request->query->all() as $value) {
            if (is_array($value)) {
                return true;
            }
        }

        return false;
    }
}
