<?php

namespace App\Vcs\Merge;

use App\Vcs\Diff\MyersDiff;

/**
 * Fusión de 3 vías por líneas (como diff3 / git merge-file).
 *
 * Se calcula qué tramos de la versión base ha cambiado cada lado («ours» y «theirs») y se recorren
 * en orden: si solo un lado toca un tramo se aplica su cambio; si los dos lo cambian igual se aplica
 * una vez; si lo cambian distinto es un conflicto, que se devuelve con las tres versiones del tramo.
 */
class ThreeWayMerge
{
    public function __construct(private readonly MyersDiff $differ = new MyersDiff) {}

    /**
     * @param  list<string>  $base
     * @param  list<string>  $ours
     * @param  list<string>  $theirs
     * @return array{clean:bool, chunks:list<array{type:string,lines?:list<string>,ours?:list<string>,base?:list<string>,theirs?:list<string>}>}
     *                                                                                                                                           type = ok | conflict
     */
    public function merge(array $base, array $ours, array $theirs): array
    {
        $base = array_values($base);
        $ours = array_values($ours);
        $theirs = array_values($theirs);

        $a = $this->hunks($base, $ours);
        $b = $this->hunks($base, $theirs);

        $chunks = [];
        $pos = 0;
        $i = $j = 0;
        $clean = true;

        while ($i < count($a) || $j < count($b)) {
            // Siguiente hunk (el que empiece antes en la base) y todos los que se solapen con él
            $start = min($a[$i]['start'] ?? PHP_INT_MAX, $b[$j]['start'] ?? PHP_INT_MAX);
            $end = $start;
            $fromA = $fromB = [];

            do {
                $grew = false;
                while (isset($a[$i]) && $this->touches($a[$i], $start, $end, $fromA || $fromB)) {
                    $end = max($end, $a[$i]['end']);
                    $fromA[] = $a[$i++];
                    $grew = true;
                }
                while (isset($b[$j]) && $this->touches($b[$j], $start, $end, $fromA || $fromB)) {
                    $end = max($end, $b[$j]['end']);
                    $fromB[] = $b[$j++];
                    $grew = true;
                }
            } while ($grew);

            // Tramo común antes del hunk
            if ($start > $pos) {
                $this->push($chunks, array_slice($base, $pos, $start - $pos));
            }

            $baseSlice = array_slice($base, $start, $end - $start);
            $oursSlice = $this->apply($base, $start, $end, $fromA);
            $theirsSlice = $this->apply($base, $start, $end, $fromB);

            if (! $fromB) {
                $this->push($chunks, $oursSlice);
            } elseif (! $fromA) {
                $this->push($chunks, $theirsSlice);
            } elseif ($oursSlice === $theirsSlice) {
                $this->push($chunks, $oursSlice); // los dos hicieron el mismo cambio
            } else {
                $clean = false;
                $chunks[] = ['type' => 'conflict', 'ours' => $oursSlice, 'base' => $baseSlice, 'theirs' => $theirsSlice];
            }

            $pos = $end;
        }

        if ($pos < count($base)) {
            $this->push($chunks, array_slice($base, $pos));
        }

        return ['clean' => $clean, 'chunks' => $chunks];
    }

    /** Líneas fusionadas si no hay conflictos (null si los hay). @return list<string>|null */
    public function mergedLines(array $result): ?array
    {
        if (! $result['clean']) {
            return null;
        }

        return array_merge(...array_map(fn ($c) => $c['lines'], $result['chunks'] ?: [['lines' => []]]));
    }

    /**
     * Texto con marcadores de conflicto (estilo git) para editarlo a mano.
     *
     * @return list<string>
     */
    public function withMarkers(array $result, string $oursLabel, string $theirsLabel): array
    {
        $lines = [];
        foreach ($result['chunks'] as $chunk) {
            if ($chunk['type'] === 'ok') {
                array_push($lines, ...$chunk['lines']);
            } else {
                $lines[] = '<<<<<<< '.$oursLabel;
                array_push($lines, ...$chunk['ours']);
                $lines[] = '=======';
                array_push($lines, ...$chunk['theirs']);
                $lines[] = '>>>>>>> '.$theirsLabel;
            }
        }

        return $lines;
    }

    // ── Internos ────────────────────────────────────────────────────────────

    /**
     * Cambios de $new respecto a $old como hunks sobre la base: [start, end) se sustituye por lines.
     * Una inserción pura tiene start === end.
     *
     * @return list<array{start:int,end:int,lines:list<string>}>
     */
    private function hunks(array $old, array $new): array
    {
        $hunks = [];
        $current = null;
        $oldPos = 0;

        foreach ($this->differ->diff($old, $new) as $op) {
            if ($op['type'] === MyersDiff::EQUAL) {
                if ($current) {
                    $hunks[] = $current;
                    $current = null;
                }
                $oldPos = $op['old'] + 1;

                continue;
            }

            $current ??= ['start' => $oldPos, 'end' => $oldPos, 'lines' => []];
            if ($op['type'] === MyersDiff::DELETE) {
                $current['end'] = $op['old'] + 1;
                $oldPos = $op['old'] + 1;
            } else {
                $current['lines'][] = $new[$op['new']];
            }
        }
        if ($current) {
            $hunks[] = $current;
        }

        return $hunks;
    }

    /**
     * ¿El hunk se solapa con el tramo [start, end)? Dos inserciones en el mismo punto también chocan;
     * un hunk que empieza justo donde acaba el tramo solo se une si el tramo ya tiene contenido.
     */
    private function touches(array $hunk, int $start, int $end, bool $open): bool
    {
        if (! $open) {
            return $hunk['start'] === $start;
        }

        return $hunk['start'] < $end || ($hunk['start'] === $end && ($hunk['start'] === $hunk['end'] || $start === $end));
    }

    /** Aplica a la base, dentro de [start, end), los hunks de un lado. @return list<string> */
    private function apply(array $base, int $start, int $end, array $hunks): array
    {
        $out = [];
        $pos = $start;
        foreach ($hunks as $h) {
            array_push($out, ...array_slice($base, $pos, $h['start'] - $pos));
            array_push($out, ...$h['lines']);
            $pos = $h['end'];
        }
        array_push($out, ...array_slice($base, $pos, $end - $pos));

        return $out;
    }

    private function push(array &$chunks, array $lines): void
    {
        if ($lines === []) {
            return;
        }
        $last = array_key_last($chunks);
        if ($last !== null && $chunks[$last]['type'] === 'ok') {
            array_push($chunks[$last]['lines'], ...$lines);
        } else {
            $chunks[] = ['type' => 'ok', 'lines' => $lines];
        }
    }
}
