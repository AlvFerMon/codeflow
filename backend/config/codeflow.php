<?php

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

];
