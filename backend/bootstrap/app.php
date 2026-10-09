<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\OptionalSanctum;
use App\Http\Middleware\RedirectUnverifiedUsers;
use App\Http\Middleware\ServerTiming;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'optional.sanctum' => OptionalSanctum::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);

        // Invitados que entran en una página con sesión obligatoria: a la pantalla de login
        $middleware->redirectGuestsTo(fn () => route('login'));

        // El contenido de los archivos editados en la web se guarda tal cual
        // (sin recortar espacios iniciales ni el salto de línea final).
        $middleware->trimStrings(except: ['file_content']);

        // Sin email verificado, la web solo muestra la pantalla de verificación
        $middleware->web(append: [RedirectUnverifiedUsers::class]);

        // Métricas de rendimiento (solo con APP_DEBUG=true)
        $middleware->prepend(ServerTiming::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
