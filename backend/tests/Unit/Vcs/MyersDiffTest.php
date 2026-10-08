<?php

namespace Tests\Unit\Vcs;

use App\Vcs\Diff\HunkBuilder;
use App\Vcs\Diff\MyersDiff;
use PHPUnit\Framework\TestCase;

class MyersDiffTest extends TestCase
{
    /** @return array{0:int,1:int} [additions, deletions] */
    private function counts(array $script): array
    {
        $add = $del = 0;
        foreach ($script as $op) {
            $add += $op['type'] === MyersDiff::INSERT ? 1 : 0;
            $del += $op['type'] === MyersDiff::DELETE ? 1 : 0;
        }

        return [$add, $del];
    }

    /** Reconstruye ambos lados a partir del guion para comprobar que es coherente. */
    private function assertScriptRebuilds(array $old, array $new, array $script): void
    {
        $rebuiltOld = $rebuiltNew = [];
        foreach ($script as $op) {
            if ($op['type'] !== MyersDiff::INSERT) {
                $rebuiltOld[] = $old[$op['old']];
            }
            if ($op['type'] !== MyersDiff::DELETE) {
                $rebuiltNew[] = $new[$op['new']];
            }
            if ($op['type'] === MyersDiff::EQUAL) {
                $this->assertSame($old[$op['old']], $new[$op['new']]);
            }
        }
        $this->assertSame($old, $rebuiltOld);
        $this->assertSame($new, $rebuiltNew);
    }

    /** Longitud de la subsecuencia común más larga (programación dinámica). */
    private function lcs(array $a, array $b): int
    {
        $prev = array_fill(0, count($b) + 1, 0);
        foreach ($a as $x) {
            $cur = [0];
            foreach ($b as $j => $y) {
                $cur[$j + 1] = $x === $y ? $prev[$j] + 1 : max($prev[$j + 1], $cur[$j]);
            }
            $prev = $cur;
        }

        return $prev[count($b)];
    }

    public function test_identical_inputs_have_no_changes(): void
    {
        $lines = ['a', 'b', 'c'];
        $script = (new MyersDiff)->diff($lines, $lines);

        $this->assertSame([0, 0], $this->counts($script));
        $this->assertCount(3, $script);
        $this->assertSame([], (new HunkBuilder)->build($script, $lines, $lines));
    }

    public function test_empty_inputs(): void
    {
        $this->assertSame([], (new MyersDiff)->diff([], []));
    }

    public function test_all_added(): void
    {
        $new = ['uno', 'dos', 'tres'];
        $script = (new MyersDiff)->diff([], $new);

        $this->assertSame([3, 0], $this->counts($script));
        $this->assertScriptRebuilds([], $new, $script);

        $hunks = (new HunkBuilder)->build($script, [], $new);
        $this->assertCount(1, $hunks);
        $this->assertSame(0, $hunks[0]['old_start']);
        $this->assertSame(0, $hunks[0]['old_lines']);
        $this->assertSame(1, $hunks[0]['new_start']);
        $this->assertSame(3, $hunks[0]['new_lines']);
        $this->assertSame(['type' => 'add', 'old' => null, 'new' => 1, 'text' => 'uno'], $hunks[0]['lines'][0]);
    }

    public function test_all_deleted(): void
    {
        $old = ['uno', 'dos'];
        $script = (new MyersDiff)->diff($old, []);

        $this->assertSame([0, 2], $this->counts($script));
        $this->assertScriptRebuilds($old, [], $script);

        $hunks = (new HunkBuilder)->build($script, $old, []);
        $this->assertSame(1, $hunks[0]['old_start']);
        $this->assertSame(2, $hunks[0]['old_lines']);
        $this->assertSame(0, $hunks[0]['new_start']);
        $this->assertSame(0, $hunks[0]['new_lines']);
    }

    public function test_repeated_lines_are_counted(): void
    {
        // array_diff daba 0 añadidos aquí porque "}" ya existía
        $old = ['if (a) {', '}'];
        $new = ['if (a) {', '}', '}', '}'];
        $script = (new MyersDiff)->diff($old, $new);

        $this->assertSame([2, 0], $this->counts($script));
        $this->assertScriptRebuilds($old, $new, $script);
    }

    public function test_moved_line_is_one_deletion_and_one_addition(): void
    {
        $old = ['a', 'b', 'c', 'd'];
        $new = ['b', 'c', 'd', 'a'];
        $script = (new MyersDiff)->diff($old, $new);

        $this->assertSame([1, 1], $this->counts($script));
        $this->assertScriptRebuilds($old, $new, $script);
    }

    public function test_modified_line(): void
    {
        $old = ['a', 'b', 'c'];
        $new = ['a', 'B', 'c'];
        $script = (new MyersDiff)->diff($old, $new);

        $this->assertSame([1, 1], $this->counts($script));
        $hunks = (new HunkBuilder)->build($script, $old, $new);
        $this->assertSame([
            ['type' => 'context', 'old' => 1, 'new' => 1, 'text' => 'a'],
            ['type' => 'del', 'old' => 2, 'new' => null, 'text' => 'b'],
            ['type' => 'add', 'old' => null, 'new' => 2, 'text' => 'B'],
            ['type' => 'context', 'old' => 3, 'new' => 3, 'text' => 'c'],
        ], $hunks[0]['lines']);
    }

    public function test_numeric_like_lines_are_compared_as_strings(): void
    {
        $script = (new MyersDiff)->diff(['1', '01', '1.0'], ['1', '1.0']);

        $this->assertSame([0, 1], $this->counts($script));
    }

    public function test_hunks_group_changes_with_three_lines_of_context(): void
    {
        $old = array_map(fn ($i) => "linea $i", range(1, 30));
        $new = $old;
        $new[1] = 'cambio 2';    // línea 2
        $new[7] = 'cambio 8';    // línea 8: separada por 5 líneas iguales -> mismo hunk
        $new[24] = 'cambio 25';  // línea 25: lejos -> hunk nuevo

        $script = (new MyersDiff)->diff($old, $new);
        $hunks = (new HunkBuilder)->build($script, $old, $new);

        $this->assertCount(2, $hunks);

        // Primer hunk: líneas 1..11 (contexto recortado al principio del archivo)
        $this->assertSame(1, $hunks[0]['old_start']);
        $this->assertSame(11, $hunks[0]['old_lines']);
        $this->assertSame(1, $hunks[0]['new_start']);
        $this->assertSame(11, $hunks[0]['new_lines']);

        // Segundo hunk: 3 de contexto antes y después de la línea 25
        $this->assertSame(22, $hunks[1]['old_start']);
        $this->assertSame(7, $hunks[1]['old_lines']);
        $this->assertSame(22, $hunks[1]['new_start']);
        $this->assertSame(7, $hunks[1]['new_lines']);
        $types = array_column($hunks[1]['lines'], 'type');
        $this->assertSame(['context', 'context', 'context', 'del', 'add', 'context', 'context', 'context'], $types);

        foreach ($hunks as $hunk) {
            $this->assertSame(['old_start', 'old_lines', 'new_start', 'new_lines', 'lines'], array_keys($hunk));
            foreach ($hunk['lines'] as $line) {
                $this->assertSame(['type', 'old', 'new', 'text'], array_keys($line));
            }
        }
    }

    public function test_hunks_split_when_gap_exceeds_twice_the_context(): void
    {
        $old = array_map(fn ($i) => "l$i", range(1, 20));
        $new = $old;
        $new[2] = 'x';   // línea 3
        $new[10] = 'y';  // línea 11: 7 líneas iguales entre medias -> hunks separados

        $hunks = (new HunkBuilder)->build((new MyersDiff)->diff($old, $new), $old, $new);

        $this->assertCount(2, $hunks);
    }

    public function test_random_inputs_produce_minimal_and_consistent_scripts(): void
    {
        mt_srand(2026);
        for ($round = 0; $round < 300; $round++) {
            $old = $this->randomLines(mt_rand(0, 25));
            $new = $this->randomLines(mt_rand(0, 25));

            $script = (new MyersDiff)->diff($old, $new);
            $this->assertScriptRebuilds($old, $new, $script);

            // Mínimo: borrados + añadidos = N + M - 2·LCS
            [$add, $del] = $this->counts($script);
            $this->assertSame(count($old) + count($new) - 2 * $this->lcs($old, $new), $add + $del);
        }
    }

    private function randomLines(int $count): array
    {
        $lines = [];
        for ($i = 0; $i < $count; $i++) {
            $lines[] = chr(ord('a') + mt_rand(0, 4));
        }

        return $lines;
    }
}
