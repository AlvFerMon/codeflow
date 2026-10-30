<?php

// Releases públicas con los instaladores (ver 'downloads' más abajo)
$downloads = rtrim((string) env('CODEFLOW_DOWNLOADS_URL', 'https://github.com/AlvFerMon/codeflow-descargas/releases'), '/');

return [

    /*
    |--------------------------------------------------------------------------
    | Clave maestra (KEK)
    |--------------------------------------------------------------------------
    |
    | Cifra las claves de cada repositorio (DEK). Formato "base64:..." con
    | 32 bytes aleatorios. Es distinta de APP_KEY y nunca se guarda en la BD.
    | Generar con: php -r "echo 'base64:'.base64_encode(random_bytes(32));"
    |
    */

    'master_key' => env('CODEFLOW_MASTER_KEY'),

    // Versión de la clave maestra con la que se cifran las DEK nuevas
    'key_version' => 1,

    /*
    |--------------------------------------------------------------------------
    | Límites del motor de control de versiones
    |--------------------------------------------------------------------------
    */

    // Ajustables con variables de entorno. En Docker deben ser coherentes con
    // docker/php/php.ini (upload_max_filesize, post_max_size, max_file_uploads),
    // nginx (client_max_body_size) y MySQL (max_allowed_packet).
    'limits' => [
        // Tamaño máximo de un archivo (MB)
        'max_file_bytes' => (int) env('CODEFLOW_MAX_FILE_MB', 50) * 1024 * 1024,
        // Archivos por commit / por subida
        'max_files_per_commit' => (int) env('CODEFLOW_MAX_FILES', 1000),
        // Tamaño total de una subida (MB)
        'max_upload_bytes' => (int) env('CODEFLOW_MAX_UPLOAD_MB', 500) * 1024 * 1024,
        'max_diff_lines' => 5000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Revisión de pull requests con IA (opcional)
    |--------------------------------------------------------------------------
    |
    | Modelo local servido por Ollama. Con 'enabled' a false la aplicación
    | funciona igual y el botón "Analizar con IA" no aparece. El código del PR
    | solo se descifra en memoria durante el análisis y nunca se guarda: en la
    | base de datos quedan el resumen y los hallazgos (sin fragmentos de código).
    |
    */

    'ai' => [
        'enabled' => (bool) env('CODEFLOW_AI_ENABLED', false),
        // Puede apuntar a otro servidor (p. ej. http://ia.midominio:11434)
        'url' => rtrim((string) env('OLLAMA_URL', 'http://ollama:11434'), '/'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5-coder:3b'),
        // Segundos máximos por petición al modelo
        'timeout' => (int) env('CODEFLOW_AI_TIMEOUT', 300),
        // Ventana de contexto del modelo (tokens): tiene que caber un lote + instrucciones + respuesta
        'context' => (int) env('CODEFLOW_AI_CONTEXT', 12288),
        // Máximo de tokens que puede escribir el modelo por lote (evita respuestas interminables)
        'max_output' => (int) env('CODEFLOW_AI_MAX_OUTPUT', 1024),
        // Tiempo que Ollama mantiene el modelo cargado en memoria entre peticiones
        'keep_alive' => env('CODEFLOW_AI_KEEP_ALIVE', '30m'),
        // Tamaño de cada lote (caracteres, ~3,5 por token): menos lotes = menos peticiones = más rápido.
        // max_chars se reparte entre todos los archivos; los grandes se recortan en vez de omitirse.
        'batch_chars' => (int) env('CODEFLOW_AI_BATCH_CHARS', 20000),
        'max_files' => (int) env('CODEFLOW_AI_MAX_FILES', 60),
        'max_chars' => (int) env('CODEFLOW_AI_MAX_CHARS', 60000),
        // Cola donde se procesan los análisis (en Docker: servicio "queue", perfil "ia")
        'queue_connection' => env('CODEFLOW_AI_QUEUE_CONNECTION', 'database'),
        'queue' => 'ai',
    ],

    /*
    |--------------------------------------------------------------------------
    | Descargas (página /descargas)
    |--------------------------------------------------------------------------
    |
    | Los instaladores pesan demasiado para guardarlos en el repositorio o en
    | Vercel, así que se publican como Release en un repositorio público de
    | GitHub con nombre fijo. Con "latest/download" el enlace siempre apunta a
    | la última versión. Si una URL se deja vacía en el .env, la página muestra "Próximamente".
    |
    */

    // Secreto con el que Vercel Cron llama a /api/cron/daily (CRON_SECRET en el panel de Vercel)
    'cron_secret' => env('CRON_SECRET'),

    'downloads' => [
        'releases' => $downloads,
        'windows' => env('CODEFLOW_DOWNLOAD_WINDOWS', $downloads.'/latest/download/CodeFlow-Setup.exe'),
        // «Próximamente» hasta que la app esté probada. Para activarla, en Vercel:
        // CODEFLOW_DOWNLOAD_ANDROID=https://github.com/AlvFerMon/codeflow-descargas/releases/latest/download/CodeFlow.apk
        'android' => env('CODEFLOW_DOWNLOAD_ANDROID'),
        'cli' => env('CODEFLOW_DOWNLOAD_CLI', $downloads.'/latest/download/codeflow.exe'),
        'version' => env('CODEFLOW_DOWNLOADS_VERSION', '1.0.0'),
    ],

    // Datos de las páginas legales (/legal/…). Cámbialos en Vercel sin tocar el código.
    'legal' => [
        'owner' => env('CODEFLOW_LEGAL_OWNER', 'El equipo de CodeFlow (Proyecto de fin de ciclo de 2.º DAM)'),
        'email' => env('CODEFLOW_LEGAL_EMAIL', env('MAIL_FROM_ADDRESS')),
        'center' => env('CODEFLOW_LEGAL_CENTER'),
        'updated' => '30 de octubre de 2026',
        'app_region' => env('CODEFLOW_LEGAL_APP_REGION', 'Unión Europea (Dublín, Irlanda)'),
        'db_region' => env('CODEFLOW_LEGAL_DB_REGION', 'Unión Europea'),
    ],

];
