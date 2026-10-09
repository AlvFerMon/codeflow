<?php

namespace App\Support;

/**
 * Qué archivos de un diff se pintan directamente al abrir la página. El resto se carga bajo
 * demanda al desplegarlo, así un commit o PR enorme no bloquea el navegador.
 */
final class DiffSelection
{
    /** Archivos que se muestran abiertos como máximo. */
    public const EAGER_FILES = 5;

    /** Líneas cambiadas (sumando archivos) que se pintan directamente como máximo. */
    public const EAGER_LINES = 1500;

    /** Líneas que se muestran de cada archivo antes del botón «Mostrar todo». */
    public const LINES_PER_FILE = 400;

    /** @param  array<int,array{path:string,additions:int,deletions:int,binary:bool,too_large:bool}>  $files */
    public static function eagerPaths(array $files): array
    {
        $paths = [];
        $budget = self::EAGER_LINES;

        foreach ($files as $file) {
            if (count($paths) >= self::EAGER_FILES) {
                break;
            }
            $lines = $file['additions'] + $file['deletions'];
            if ($file['binary'] || $file['too_large'] || $lines > $budget) {
                continue;
            }
            $paths[] = $file['path'];
            $budget -= $lines;
        }

        return $paths;
    }

    /** Añade los hunks calculados a las entradas del resumen (por ruta). */
    public static function merge(array $summary, array $detailed): array
    {
        $byPath = collect($detailed)->keyBy('path');

        return array_map(fn (array $file) => isset($byPath[$file['path']]['hunks'])
            ? $file + ['hunks' => $byPath[$file['path']]['hunks']]
            : $file, $summary);
    }
}
