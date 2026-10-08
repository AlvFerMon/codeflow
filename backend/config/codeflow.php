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

    'limits' => [
        'max_file_bytes' => (int) env('CODEFLOW_MAX_FILE_BYTES', 5 * 1024 * 1024),
        'max_files_per_commit' => 200,
        'max_diff_lines' => 5000,
    ],

];
