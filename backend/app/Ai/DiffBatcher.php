<?php

namespace App\Ai;

/**
 * Convierte los archivos de un diff (con hunks) en lotes de texto para el modelo.
 *
 * - Se omiten solo los archivos que no tiene sentido revisar: binarios, borrados, generados
 *   (dependencias, lockfiles, minificados, compilados) o sin líneas de texto cambiadas.
 * - El presupuesto total de caracteres se reparte entre TODOS los demás archivos: los pequeños
 *   entran enteros y los grandes se recortan (el modelo ve el principio de su cambio), en vez
 *   de dejar fuera los archivos que ya no caben.
 * - Los archivos se agrupan en lotes de hasta $batchChars caracteres (una petición al modelo por lote).
 */
class DiffBatcher
{
    /** Motivos de omisión (se muestran en la web). */
    public const SKIP_REASONS = [
        'binary' => 'binario',
        'deleted' => 'borrado',
        'generated' => 'generado o dependencia',
        'too_large' => 'demasiado grande para mostrar el diff',
        'empty' => 'sin cambios de texto',
        'limit' => 'límite de archivos por análisis',
    ];

    private const GENERATED_NAMES = [
        'package-lock.json', 'yarn.lock', 'pnpm-lock.yaml', 'composer.lock', 'cargo.lock',
        'gemfile.lock', 'poetry.lock', 'go.sum', 'gradle-wrapper.jar', 'gradlew', 'gradlew.bat',
    ];

    private const GENERATED_DIRS = [
        'node_modules', 'vendor', 'dist', 'build', 'public/build', '.gradle', '.idea', '.vscode',
        'obj', 'target', 'coverage', '__pycache__', '.next', '.nuxt',
    ];

    private const GENERATED_SUFFIXES = ['.min.js', '.min.css', '.map', '.svg', '.lock'];

    public function __construct(
        private readonly int $batchChars = 20000,
        private readonly int $maxFiles = 40,
        private readonly int $maxChars = 60000,
    ) {}

    /**
     * @param  array<int,array>  $files  Salida de VcsService::diff(..., withHunks: true)
     * @return array{
     *     batches: array<int,array{text:string,paths:array<int,string>}>,
     *     analyzed:int, truncated:int,
     *     skipped: array<int,array{path:string,reason:string}>
     * }
     */
    public function batch(array $files): array
    {
        $skipped = [];
        $candidates = [];

        foreach ($files as $file) {
            $path = (string) ($file['path'] ?? '');
            $reason = $this->skipReason($file);
            if ($reason !== null) {
                $skipped[] = ['path' => $path, 'reason' => $reason];

                continue;
            }
            if (count($candidates) >= $this->maxFiles) {
                $skipped[] = ['path' => $path, 'reason' => 'limit'];

                continue;
            }
            $candidates[] = ['path' => $path, 'text' => $this->render($file)];
        }

        $allowance = $this->allocate(array_map(fn ($c) => strlen($c['text']), $candidates));

        $batches = [];
        $current = ['text' => '', 'paths' => []];
        $truncated = 0;

        foreach ($candidates as $i => $candidate) {
            $text = $candidate['text'];
            if (strlen($text) > $allowance[$i]) {
                $text = $this->cut($text, $allowance[$i]);
                $truncated++;
            }

            if ($current['paths'] !== [] && strlen($current['text']) + strlen($text) > $this->batchChars) {
                $batches[] = $current;
                $current = ['text' => '', 'paths' => []];
            }
            $current['text'] .= $text;
            $current['paths'][] = $candidate['path'];
        }

        if ($current['paths'] !== []) {
            $batches[] = $current;
        }

        return [
            'batches' => $batches,
            'analyzed' => count($candidates),
            'truncated' => $truncated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Reparto "por niveles" del presupuesto total: los archivos pequeños reciben lo que ocupan y lo
     * que sobra se reparte a partes iguales entre los grandes. Ningún archivo pasa de un lote.
     *
     * @param  array<int,int>  $sizes
     * @return array<int,int>
     */
    private function allocate(array $sizes): array
    {
        $allowance = [];
        $remaining = $this->maxChars;
        $pending = count($sizes);

        asort($sizes);
        foreach ($sizes as $i => $size) {
            $share = intdiv($remaining, max(1, $pending));
            $allowance[$i] = min($size, $share, $this->batchChars);
            $remaining -= $allowance[$i];
            $pending--;
        }

        return $allowance;
    }

    /** Recorta en un salto de línea e indica cuántas líneas se han quedado sin revisar. */
    private function cut(string $text, int $limit): string
    {
        $marker = 80; // espacio reservado para el aviso
        $head = mb_strcut($text, 0, max(0, $limit - $marker));
        $lastBreak = strrpos($head, "\n");
        if ($lastBreak !== false) {
            $head = substr($head, 0, $lastBreak + 1);
        }
        $rest = max(1, substr_count($text, "\n", strlen($head)) - 1);

        return $head."      … ({$rest} líneas más de este archivo sin revisar)\n\n";
    }

    private function skipReason(array $file): ?string
    {
        return match (true) {
            (bool) ($file['binary'] ?? false) => 'binary',
            ($file['status'] ?? '') === 'deleted' => 'deleted',
            self::isGenerated((string) ($file['path'] ?? '')) => 'generated',
            (bool) ($file['too_large'] ?? false) => 'too_large',
            empty($file['hunks']) => 'empty',
            default => null,
        };
    }

    /** Dependencias, lockfiles, minificados y salidas de compilación: no aportan a la revisión. */
    public static function isGenerated(string $path): bool
    {
        $lower = strtolower(str_replace('\\', '/', $path));
        if (in_array(basename($lower), self::GENERATED_NAMES, true)) {
            return true;
        }
        foreach (self::GENERATED_SUFFIXES as $suffix) {
            if (str_ends_with($lower, $suffix)) {
                return true;
            }
        }
        foreach (self::GENERATED_DIRS as $dir) {
            if (str_starts_with($lower, $dir.'/') || str_contains($lower, '/'.$dir.'/')) {
                return true;
            }
        }

        return false;
    }

    /** Texto de un archivo: cabecera y líneas con número del archivo nuevo. */
    private function render(array $file): string
    {
        $out = "### archivo: {$file['path']} ({$file['status']})\n";
        foreach ($file['hunks'] as $hunk) {
            $out .= "@@\n";
            foreach ($hunk['lines'] as $line) {
                $text = rtrim((string) $line['text'], "\r\n");
                $out .= match ($line['type']) {
                    'add' => str_pad((string) $line['new'], 5, ' ', STR_PAD_LEFT)." + {$text}\n",
                    'del' => "      - {$text}\n",
                    default => str_pad((string) $line['new'], 5, ' ', STR_PAD_LEFT)."   {$text}\n",
                };
            }
        }

        return $out."\n";
    }
}
