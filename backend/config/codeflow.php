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

];
