<?php

use Illuminate\Http\Request;

// Punto de entrada en Vercel. El sistema de archivos de la función es de solo lectura
// salvo /tmp, así que todo lo que Laravel escribe (cachés, vistas compiladas, logs,
// sesiones) se redirige allí. Las variables definidas en el panel de Vercel tienen prioridad.
$defaults = [
    'APP_CONFIG_CACHE' => '/tmp/bootstrap/config.php',
    'APP_EVENTS_CACHE' => '/tmp/bootstrap/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/bootstrap/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/bootstrap/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/bootstrap/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/storage/framework/views',
    'LOG_CHANNEL' => 'stderr',
    'SESSION_DRIVER' => 'cookie',
    'CACHE_STORE' => 'array',
    'TRUSTED_PROXIES' => '*',
];

foreach ($defaults as $key => $value) {
    if (getenv($key) === false && ! isset($_ENV[$key]) && ! isset($_SERVER[$key])) {
        putenv("$key=$value");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

foreach (['/tmp/bootstrap', '/tmp/storage/app/private', '/tmp/storage/app/public',
    '/tmp/storage/framework/cache/data', '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views', '/tmp/storage/logs'] as $dir) {
    is_dir($dir) || mkdir($dir, 0755, true);
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->useStoragePath('/tmp/storage');

$app->handleRequest(Request::capture());
