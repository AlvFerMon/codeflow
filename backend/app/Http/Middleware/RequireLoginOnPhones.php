<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Web\Mobile\MobileController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En un teléfono la web exige iniciar sesión o crear una cuenta: sin sesión solo se ven las
 * pantallas de acceso. Tras entrar, la portada lleva a la versión móvil (/m). Explorar repositorios
 * públicos no está disponible en el móvil.
 * En el ordenador los visitantes sin sesión navegan como siempre (repositorios públicos).
 */
class RequireLoginOnPhones
{
    private const ALLOWED = [
        'login', 'login.store', 'register', 'register.store',
        'password.request', 'password.email', 'password.reset', 'password.reset.query', 'password.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! MobileController::isPhone($request)) {
            return $next($request);
        }

        if (! $request->user() && ! $request->routeIs(...self::ALLOWED)) {
            return redirect()->guest(route('login'));
        }

        // En el móvil no existe "Explorar repositorios públicos"
        if ($request->user() && $request->routeIs('explore')) {
            return redirect()->route('mobile.activity');
        }

        return $next($request);
    }
}
