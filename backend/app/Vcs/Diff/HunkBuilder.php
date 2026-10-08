<?php

namespace App\Vcs\Diff;

/**
 * Agrupa un guion de edición de MyersDiff en "hunks" de formato unificado
 * (como `git diff`), con N líneas de contexto alrededor de cada cambio.
 *
 * Forma de cada hunk (contrato usado por la web y Android):
 * ['old_start'=>int,'old_lines'=>int,'new_start'=>int,'new_lines'=>int,
 *  'lines'=>[['type'=>'context'|'add'|'del','old'=>?int,'new'=>?int,'text'=>string]]]
 * Los números de línea empiezan en 1.
 */
class HunkBuilder
{
    public function __construct(private readonly int $context = 3) {}

    /**
     * @param  list<array{type:string,old:?int,new:?int}>  $script
     * @param  array<int,string>  $old
     * @param  array<int,string>  $new
     * @return list<array<string,mixed>>
     */
    public function build(array $script, array $old, array $new): array
    {
        $old = array_values($old);
        $new = array_values($new);

        // Rangos [inicio, fin] del guion que forman cada hunk
        $ranges = [];
        foreach ($script as $index => $op) {
            if ($op['type'] === MyersDiff::EQUAL) {
                continue;
            }
            $start = max(0, $index - $this->context);
            $end = min(count($script) - 1, $index + $this->context);
            $last = count($ranges) - 1;

            if ($last >= 0 && $start <= $ranges[$last][1] + 1) {
                $ranges[$last][1] = max($ranges[$last][1], $end);
            } else {
                $ranges[] = [$start, $end];
            }
        }

        $hunks = [];
        foreach ($ranges as [$start, $end]) {
            $lines = [];
            $oldCount = $newCount = 0;
            $oldFirst = $newFirst = null;

            for ($i = $start; $i <= $end; $i++) {
                $op = $script[$i];
                $oldNo = $op['old'] === null ? null : $op['old'] + 1;
                $newNo = $op['new'] === null ? null : $op['new'] + 1;

                if ($oldNo !== null) {
                    $oldFirst ??= $oldNo;
                    $oldCount++;
                }
                if ($newNo !== null) {
                    $newFirst ??= $newNo;
                    $newCount++;
                }

                $lines[] = match ($op['type']) {
                    MyersDiff::EQUAL => ['type' => 'context', 'old' => $oldNo, 'new' => $newNo, 'text' => $old[$op['old']]],
                    MyersDiff::DELETE => ['type' => 'del', 'old' => $oldNo, 'new' => null, 'text' => $old[$op['old']]],
                    default => ['type' => 'add', 'old' => null, 'new' => $newNo, 'text' => $new[$op['new']]],
                };
            }

            $hunks[] = [
                // Si un lado no tiene líneas, se indica la línea anterior (convención de diff unificado)
                'old_start' => $oldFirst ?? $this->linesBefore($script, $start, 'old'),
                'old_lines' => $oldCount,
                'new_start' => $newFirst ?? $this->linesBefore($script, $start, 'new'),
                'new_lines' => $newCount,
                'lines' => $lines,
            ];
        }

        return $hunks;
    }

    /** Número de líneas de un lado que aparecen en el guion antes de $index. */
    private function linesBefore(array $script, int $index, string $side): int
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if ($script[$i][$side] !== null) {
                return $script[$i][$side] + 1;
            }
        }

        return 0;
    }
}
