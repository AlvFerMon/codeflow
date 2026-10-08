<?php

namespace App\Vcs\Diff;

/**
 * Diff de líneas con el algoritmo de Myers O(ND) en su variante de espacio
 * lineal ("middle snake" + divide y vencerás), como GNU diff o diff-match-patch.
 *
 * Las líneas se convierten antes en enteros para comparar más rápido y se
 * recortan el prefijo y el sufijo comunes de cada subproblema.
 */
class MyersDiff
{
    public const EQUAL = 'equal';

    public const DELETE = 'delete';

    public const INSERT = 'insert';

    /** @var array<int,int> */
    private array $a = [];

    /** @var array<int,int> */
    private array $b = [];

    /** @var array<int,bool> líneas antiguas borradas */
    private array $deleted = [];

    /** @var array<int,bool> líneas nuevas añadidas */
    private array $inserted = [];

    /**
     * Guion de edición mínimo entre dos listas de líneas.
     * Los índices son de base 0; en cada bloque de cambios van primero los borrados.
     *
     * @param  array<int,string>  $old
     * @param  array<int,string>  $new
     * @return list<array{type:string,old:?int,new:?int}>
     */
    public function diff(array $old, array $new): array
    {
        $ids = [];
        $a = $b = [];
        foreach (array_values($old) as $line) {
            $a[] = $ids[$line] ??= count($ids);
        }
        foreach (array_values($new) as $line) {
            $b[] = $ids[$line] ??= count($ids);
        }

        $n = count($a);
        $m = count($b);
        $deleted = $n ? array_fill(0, $n, false) : [];
        $inserted = $m ? array_fill(0, $m, false) : [];

        // Una línea que solo existe en un lado nunca puede emparejarse: se marca
        // directamente y Myers trabaja con el resto (mismo resultado mínimo y
        // mucho más rápido cuando un archivo se reescribe casi entero).
        $inA = array_flip($a);
        $inB = array_flip($b);
        $aIndex = $bIndex = [];
        $this->a = $this->b = [];
        foreach ($a as $i => $id) {
            if (isset($inB[$id])) {
                $aIndex[] = $i;
                $this->a[] = $id;
            } else {
                $deleted[$i] = true;
            }
        }
        foreach ($b as $j => $id) {
            if (isset($inA[$id])) {
                $bIndex[] = $j;
                $this->b[] = $id;
            } else {
                $inserted[$j] = true;
            }
        }

        $this->deleted = $this->a ? array_fill(0, count($this->a), false) : [];
        $this->inserted = $this->b ? array_fill(0, count($this->b), false) : [];
        $this->compare(0, count($this->a), 0, count($this->b));

        foreach ($this->deleted as $i => $flag) {
            if ($flag) {
                $deleted[$aIndex[$i]] = true;
            }
        }
        foreach ($this->inserted as $j => $flag) {
            if ($flag) {
                $inserted[$bIndex[$j]] = true;
            }
        }

        $script = [];
        $i = $j = 0;
        while ($i < $n || $j < $m) {
            if ($i < $n && $deleted[$i]) {
                $script[] = ['type' => self::DELETE, 'old' => $i++, 'new' => null];
            } elseif ($j < $m && $inserted[$j]) {
                $script[] = ['type' => self::INSERT, 'old' => null, 'new' => $j++];
            } else {
                $script[] = ['type' => self::EQUAL, 'old' => $i++, 'new' => $j++];
            }
        }

        $this->a = $this->b = $this->deleted = $this->inserted = [];

        return $script;
    }

    /** Diferencia a[aLo..aHi) frente a b[bLo..bHi) marcando borrados e inserciones. */
    private function compare(int $aLo, int $aHi, int $bLo, int $bHi): void
    {
        while ($aLo < $aHi && $bLo < $bHi && $this->a[$aLo] === $this->b[$bLo]) {
            $aLo++;
            $bLo++;
        }
        while ($aHi > $aLo && $bHi > $bLo && $this->a[$aHi - 1] === $this->b[$bHi - 1]) {
            $aHi--;
            $bHi--;
        }

        if ($aLo === $aHi) {
            for ($j = $bLo; $j < $bHi; $j++) {
                $this->inserted[$j] = true;
            }

            return;
        }
        if ($bLo === $bHi) {
            for ($i = $aLo; $i < $aHi; $i++) {
                $this->deleted[$i] = true;
            }

            return;
        }

        $split = $this->middleSnake($aLo, $aHi, $bLo, $bHi);

        if ($split === null) {
            for ($i = $aLo; $i < $aHi; $i++) {
                $this->deleted[$i] = true;
            }
            for ($j = $bLo; $j < $bHi; $j++) {
                $this->inserted[$j] = true;
            }

            return;
        }

        [$x, $y] = $split;
        $this->compare($aLo, $aLo + $x, $bLo, $bLo + $y);
        $this->compare($aLo + $x, $aHi, $bLo + $y, $bHi);
    }

    /**
     * Busca el "middle snake": recorre el grafo de edición a la vez desde el
     * principio y desde el final hasta que ambos caminos se solapan.
     *
     * @return array{0:int,1:int}|null punto de corte relativo (x, y)
     */
    private function middleSnake(int $aLo, int $aHi, int $bLo, int $bHi): ?array
    {
        $n = $aHi - $aLo;
        $m = $bHi - $bLo;
        $maxD = intdiv($n + $m + 1, 2);
        $offset = $maxD;
        $length = 2 * $maxD + 2;
        $v1 = array_fill(0, $length, -1);
        $v2 = array_fill(0, $length, -1);
        $v1[$offset + 1] = 0;
        $v2[$offset + 1] = 0;
        $delta = $n - $m;
        $front = ($delta % 2) !== 0;
        $k1start = $k1end = $k2start = $k2end = 0;

        for ($d = 0; $d < $maxD; $d++) {
            // Camino hacia delante
            for ($k1 = -$d + $k1start; $k1 <= $d - $k1end; $k1 += 2) {
                $k1Offset = $offset + $k1;
                if ($k1 === -$d || ($k1 !== $d && $v1[$k1Offset - 1] < $v1[$k1Offset + 1])) {
                    $x1 = $v1[$k1Offset + 1];
                } else {
                    $x1 = $v1[$k1Offset - 1] + 1;
                }
                $y1 = $x1 - $k1;
                while ($x1 < $n && $y1 < $m && $this->a[$aLo + $x1] === $this->b[$bLo + $y1]) {
                    $x1++;
                    $y1++;
                }
                $v1[$k1Offset] = $x1;

                if ($x1 > $n) {
                    $k1end += 2;
                } elseif ($y1 > $m) {
                    $k1start += 2;
                } elseif ($front) {
                    $k2Offset = $offset + $delta - $k1;
                    if ($k2Offset >= 0 && $k2Offset < $length && $v2[$k2Offset] !== -1) {
                        if ($x1 >= $n - $v2[$k2Offset]) {
                            return [$x1, $y1];
                        }
                    }
                }
            }

            // Camino hacia atrás
            for ($k2 = -$d + $k2start; $k2 <= $d - $k2end; $k2 += 2) {
                $k2Offset = $offset + $k2;
                if ($k2 === -$d || ($k2 !== $d && $v2[$k2Offset - 1] < $v2[$k2Offset + 1])) {
                    $x2 = $v2[$k2Offset + 1];
                } else {
                    $x2 = $v2[$k2Offset - 1] + 1;
                }
                $y2 = $x2 - $k2;
                while ($x2 < $n && $y2 < $m && $this->a[$aHi - 1 - $x2] === $this->b[$bHi - 1 - $y2]) {
                    $x2++;
                    $y2++;
                }
                $v2[$k2Offset] = $x2;

                if ($x2 > $n) {
                    $k2end += 2;
                } elseif ($y2 > $m) {
                    $k2start += 2;
                } elseif (! $front) {
                    $k1Offset = $offset + $delta - $k2;
                    if ($k1Offset >= 0 && $k1Offset < $length && $v1[$k1Offset] !== -1) {
                        $x1 = $v1[$k1Offset];
                        $y1 = $offset + $x1 - $k1Offset;
                        if ($x1 >= $n - $x2) {
                            return [$x1, $y1];
                        }
                    }
                }
            }
        }

        return null;
    }
}
