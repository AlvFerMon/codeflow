<?php

namespace App\Ai;

/** Resultado de revisar un lote: resumen y hallazgos ya validados. */
final class ReviewResult
{
    /**
     * @param  array<int,array{path:string,line:?int,severity:string,category:string,title:string,explanation:string,suggestion:?string}>  $findings
     */
    public function __construct(
        public readonly string $summary,
        public readonly array $findings,
    ) {}
}
