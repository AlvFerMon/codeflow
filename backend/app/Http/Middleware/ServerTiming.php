<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo en modo debug: añade la cabecera Server-Timing con el tiempo total de la
 * petición, el tiempo en base de datos y el número de consultas. Se ve en las
 * DevTools del navegador (pestaña Network → Timing) y sirve para detectar páginas lentas.
 */
class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.debug')) {
            return $next($request);
        }

        $queries = 0;
        $dbMs = 0.0;
        DB::listen(function ($query) use (&$queries, &$dbMs) {
            $queries++;
            $dbMs += $query->time;
        });

        $response = $next($request);

        $totalMs = (microtime(true) - (defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT'))) * 1000;
        $response->headers->set('Server-Timing', sprintf(
            'app;dur=%.1f, db;dur=%.1f;desc="%d consultas"',
            $totalMs, $dbMs, $queries
        ));

        return $response;
    }
}
