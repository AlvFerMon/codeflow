<?php

namespace Tests\Unit;

use App\Vcs\Merge\ThreeWayMerge;
use PHPUnit\Framework\TestCase;

class ThreeWayMergeTest extends TestCase
{
    private function merge(array $base, array $ours, array $theirs): array
    {
        $m = new ThreeWayMerge;

        return [$m, $m->merge($base, $ours, $theirs)];
    }

    public function test_changes_in_different_lines_merge_cleanly(): void
    {
        $base = ['a', 'b', 'c', 'd', 'e', 'f'];
        [$m, $r] = $this->merge($base, ['A', 'b', 'c', 'd', 'e', 'f'], ['a', 'b', 'c', 'd', 'e', 'F']);

        $this->assertTrue($r['clean']);
        $this->assertSame(['A', 'b', 'c', 'd', 'e', 'F'], $m->mergedLines($r));
    }

    public function test_insertions_and_deletions_on_each_side(): void
    {
        $base = ['uno', 'dos', 'tres', 'cuatro', 'cinco'];
        $ours = ['cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco'];      // añade al principio
        $theirs = ['uno', 'dos', 'cuatro', 'cinco', 'seis'];           // borra «tres» y añade al final
        [$m, $r] = $this->merge($base, $ours, $theirs);

        $this->assertSame(['cero', 'uno', 'dos', 'cuatro', 'cinco', 'seis'], $m->mergedLines($r));
    }

    public function test_same_change_on_both_sides_is_not_a_conflict(): void
    {
        [$m, $r] = $this->merge(['x', 'y'], ['x', 'Y'], ['x', 'Y']);
        $this->assertSame(['x', 'Y'], $m->mergedLines($r));
    }

    public function test_different_changes_to_the_same_line_conflict_with_markers(): void
    {
        [$m, $r] = $this->merge(['a', 'b', 'c'], ['a', 'B1', 'c'], ['a', 'B2', 'c']);

        $this->assertFalse($r['clean']);
        $this->assertNull($m->mergedLines($r));
        $this->assertSame(['a', '<<<<<<< mía', 'B1', '=======', 'B2', '>>>>>>> suya', 'c'], $m->withMarkers($r, 'mía', 'suya'));
        $conflict = collect($r['chunks'])->firstWhere('type', 'conflict');
        $this->assertSame(['b'], $conflict['base']);
    }

    public function test_insertions_at_the_same_point_conflict(): void
    {
        [, $r] = $this->merge(['a', 'b'], ['a', 'X', 'b'], ['a', 'Y', 'b']);
        $this->assertFalse($r['clean']);
    }

    public function test_empty_base_and_one_side_unchanged(): void
    {
        [$m, $r] = $this->merge([], [], ['nuevo']);
        $this->assertSame(['nuevo'], $m->mergedLines($r));

        [$m, $r] = $this->merge(['a'], ['a'], ['a']);
        $this->assertSame(['a'], $m->mergedLines($r));
    }

    public function test_many_separate_edits(): void
    {
        $base = array_map(fn ($i) => "línea $i", range(1, 200));
        $ours = $base;
        $theirs = $base;
        $ours[9] = 'cambio mío';
        $ours[150] = 'otro mío';
        $theirs[50] = 'cambio suyo';
        array_splice($theirs, 100, 3);
        $expected = $base;
        $expected[9] = 'cambio mío';
        $expected[150] = 'otro mío';
        $expected[50] = 'cambio suyo';
        array_splice($expected, 100, 3);

        [$m, $r] = $this->merge($base, $ours, $theirs);
        $this->assertSame($expected, $m->mergedLines($r));
    }
}
