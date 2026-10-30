<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\OptionalSanctum;
use App\Http\Middleware\RedirectUnverifiedUsers;
use App\Http\Middleware\RequireLoginOnPhones;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ServerTiming;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        // El contenido de los archivos se guarda tal cual (sin recortar espacios iniciales ni el salto
        // de línea final): editor web, commits de la API/CLI y resolución de conflictos.
        $middleware->trimStrings(except: ['file_content', 'changes.*.content', 'files.*.content']);

        // Sin email verificado, la web solo muestra la pantalla de verificación
        $middleware->web(append: [RedirectUnverifiedUsers::class, RequireLoginOnPhones::class]);

        // Producción detrás de Caddy (HTTPS) → nginx: confiar en sus cabeceras X-Forwarded-*
        // para que Laravel genere URLs https y vea la IP real. Solo si TRUSTED_PROXIES está definida.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Métricas de rendimiento (solo con APP_DEBUG=true)
        $middleware->prepend(ServerTiming::class);

        // Cabeceras de seguridad (iframes, tipos MIME, referer…) en todas las respuestas
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // La API no dice qué modelo no existe («No query results for model [App\\Models\\…]»)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($e->getPrevious() instanceof ModelNotFoundException && ($request->is('api/*') || $request->expectsJson())) {
                return response()->json(['message' => 'No encontrado.'], 404);
            }
        });

        // En Vercel el log de cada petición se recorta y se pierde el mensaje del error: allí se escribe
        // solo un resumen corto (sin la traza completa). Devolver false evita el log normal.
        $exceptions->report(function (Throwable $e) {
            if (! env('VERCEL') && env('LOG_CHANNEL') !== 'stderr') {
                return;
            }
            $where = fn (Throwable $t) => str_replace(base_path().'/', '', $t->getFile()).':'.$t->getLine();
            $line = '[codeflow] '.$e::class.': '.$e->getMessage().' @ '.$where($e);
            if ($previous = $e->getPrevious()) {
                $line .= ' | causa: '.$previous::class.': '.$previous->getMessage();
            }
            error_log(mb_substr($line, 0, 1500));

            return false;
        });
    })->create();
