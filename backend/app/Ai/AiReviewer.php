<?php

namespace App\Ai;

/**
 * Revisor de código con IA. El núcleo de CodeFlow no depende de él: si la IA está
 * desactivada se usa NullReviewer y la aplicación funciona igual.
 */
interface AiReviewer
{
    /**
     * Revisa un lote de diff.
     *
     * @param  string  $diff  Diff en texto (ver DiffBatcher), en memoria: nunca se guarda.
     * @param  array<int,string>  $paths  Archivos incluidos en el lote (los hallazgos de otros se descartan).
     *
     * @throws AiUnavailableException si el servicio de IA no responde o no está configurado.
     */
    public function review(string $diff, array $paths): ReviewResult;

    /** Nombre del modelo (se guarda con cada análisis). */
    public function model(): string;
}
