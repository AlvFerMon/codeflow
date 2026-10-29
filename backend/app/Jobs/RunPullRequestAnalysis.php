<?php

namespace App\Jobs;

use App\Models\AiAnalysis;
use App\Services\PullRequestAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/** Análisis con IA de un pull request, en segundo plano (cola "ai"). */
class RunPullRequestAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Un solo intento: si falla, el usuario puede volver a lanzarlo desde el PR. */
    public int $tries = 1;

    /** Segundos máximos del trabajo completo (varios lotes al modelo). */
    public int $timeout = 1800;

    public function __construct(public readonly int $analysisId) {}

    public function handle(PullRequestAnalysisService $service): void
    {
        $analysis = AiAnalysis::find($this->analysisId);
        if ($analysis) {
            $service->run($analysis);
        }
    }

    /** Si el trabajo muere (p. ej. por tiempo), el análisis no se queda "en curso" para siempre. */
    public function failed(?Throwable $e): void
    {
        $analysis = AiAnalysis::find($this->analysisId);
        if ($analysis?->isInProgress()) {
            app(PullRequestAnalysisService::class)->fail($analysis, 'El análisis tardó demasiado o se interrumpió. Inténtalo de nuevo.');
        }
    }
}
