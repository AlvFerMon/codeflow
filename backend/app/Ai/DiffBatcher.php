<?php

namespace App\Ai;

/**
 * Convierte los archivos de un diff (con hunks) en lotes de texto para el modelo, sin pasarse
 * del tamaño de lote ni del máximo total. Los archivos binarios, demasiado grandes o que no
 * caben se cuentan como omitidos.
 */
class DiffBatcher
{
    public function __construct(
        private readonly int $batchChars = 12000,
        private readonly int $maxFiles = 40,
        private readonly int $maxChars = 60000,
    ) {}

    /**
     * @param  array<int,array>  $files  Salida de VcsService::diff(..., withHunks: true)
     * @return array{batches: array<int,array{text:string,paths:array<int,string>}>, analyzed:int, skipped:int}
     */
    public function batch(array $files): array
    {
        $batches = [];
        $current = ['text' => '', 'paths' => []];
        $analyzed = 0;
        $skipped = 0;
        $total = 0;

        foreach ($files as $file) {
            $text = $this->render($file);

            if ($text === null || $analyzed >= $this->maxFiles || $total + strlen($text) > $this->maxChars) {
                $skipped++;

                continue;
            }

            // Un archivo más grande que un lote se recorta (el modelo ve el principio del cambio)
            if (strlen($text) > $this->batchChars) {
                $text = mb_strcut($text, 0, $this->batchChars)."\n[… diff recortado]\n";
            }

            if ($current['paths'] !== [] && strlen($current['text']) + strlen($text) > $this->batchChars) {
                $batches[] = $current;
                $current = ['text' => '', 'paths' => []];
            }

            $current['text'] .= $text;
            $current['paths'][] = $file['path'];
            $total += strlen($text);
            $analyzed++;
        }

        if ($current['paths'] !== []) {
            $batches[] = $current;
        }

        return ['batches' => $batches, 'analyzed' => $analyzed, 'skipped' => $skipped];
    }

    /** Texto de un archivo: cabecera y líneas con número del archivo nuevo. null si no se puede revisar. */
    private function render(array $file): ?string
    {
        if (($file['binary'] ?? false) || ($file['too_large'] ?? false) || ($file['status'] ?? '') === 'deleted' || empty($file['hunks'])) {
            return null;
        }

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
