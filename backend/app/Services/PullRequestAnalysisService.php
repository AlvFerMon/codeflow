<?php

namespace App\Services;

use App\Ai\AiReviewer;
use App\Ai\AiUnavailableException;
use App\Ai\DiffBatcher;
use App\Jobs\RunPullRequestAnalysis;
use App\Models\AiAnalysis;
use App\Models\PullRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Revisión de pull requests con IA (opcional).
 *
 * request() crea el análisis y lo encola; run() (en la cola "ai") saca el diff del PR,
 * lo descifra SOLO en memoria, lo envía por lotes al modelo y guarda el resumen y los
 * hallazgos. El código nunca se escribe en disco ni en la base de datos.
 */
class PullRequestAnalysisService
{
    public function __construct(
        private readonly PullRequestService $pullRequests,
        private readonly AiReviewer $reviewer,
    ) {}

    public static function enabled(): bool
    {
        return (bool) config('codeflow.ai.enabled');
    }

    /** Crea y encola un análisis. Si ya hay uno en curso para el PR, devuelve ese. */
    public function request(PullRequest $pullRequest, User $user): AiAnalysis
    {
        if (! self::enabled()) {
            throw new AiUnavailableException('La revisión con IA está desactivada.');
        }

        $current = $pullRequest->latestAiAnalysis()->first();
        if ($current?->isInProgress()) {
            // Uno atascado (p. ej. la cola estaba parada) no bloquea un análisis nuevo
            if ($current->created_at->gt(now()->subMinutes(30))) {
                return $current;
            }
            $this->fail($current, 'El análisis no llegó a terminar.');
        }

        [, $head] = $this->pullRequests->diffRange($pullRequest);
        if (! $head) {
            throw new AiUnavailableException('El pull request no tiene cambios que analizar.');
        }

        $analysis = AiAnalysis::create([
            'pull_request_id' => $pullRequest->id,
            'requested_by' => $user->id,
            'status' => AiAnalysis::PENDING,
            'model' => $this->reviewer->model(),
            'head_commit_id' => $head,
        ]);

        RunPullRequestAnalysis::dispatch($analysis->id)
            ->onConnection(config('codeflow.ai.queue_connection'))
            ->onQueue(config('codeflow.ai.queue'));

        return $analysis;
    }

    /** Ejecuta el análisis (lo llama el job). Nunca lanza: los errores quedan en el propio análisis. */
    public function run(AiAnalysis $analysis): void
    {
        if (! $analysis->isInProgress()) {
            return;
        }

        $analysis->update(['status' => AiAnalysis::RUNNING, 'started_at' => now()]);

        try {
            $pullRequest = $analysis->pullRequest()->with(['sourceBranch:id,head_commit_id', 'targetBranch:id,head_commit_id'])->firstOrFail();

            // Diff con las líneas: el contenido se descifra en memoria y vive solo en esta función
            $files = $this->pullRequests->files($pullRequest, withHunks: true);
            $batcher = new DiffBatcher(
                batchChars: (int) config('codeflow.ai.batch_chars'),
                maxFiles: (int) config('codeflow.ai.max_files'),
                maxChars: (int) config('codeflow.ai.max_chars'),
            );
            ['batches' => $batches, 'analyzed' => $analyzed, 'skipped' => $skipped] = $batcher->batch($files);
            unset($files);

            if ($batches === []) {
                throw new AiUnavailableException('No hay archivos de texto que analizar (binarios, borrados o demasiado grandes).');
            }

            $summaries = [];
            $findings = [];
            foreach ($batches as $batch) {
                $result = $this->reviewer->review($batch['text'], $batch['paths']);
                if ($result->summary !== '') {
                    $summaries[] = $result->summary;
                }
                array_push($findings, ...$result->findings);
            }
            unset($batches);

            DB::transaction(function () use ($analysis, $findings, $summaries, $analyzed, $skipped) {
                $analysis->findings()->delete();
                foreach ($findings as $finding) {
                    $analysis->findings()->create($finding);
                }
                $analysis->update([
                    'status' => AiAnalysis::DONE,
                    'summary' => mb_substr(implode("\n\n", $summaries), 0, 4000) ?: null,
                    'files_analyzed' => $analyzed,
                    'files_skipped' => $skipped,
                    'findings_count' => count($findings),
                    'finished_at' => now(),
                ]);
            });
        } catch (AiUnavailableException $e) {
            $this->fail($analysis, $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Análisis con IA fallido', ['analysis' => $analysis->id, 'error' => $e->getMessage()]);
            $this->fail($analysis, 'Error inesperado durante el análisis. Inténtalo de nuevo.');
        }
    }

    public function fail(AiAnalysis $analysis, string $message): void
    {
        $analysis->update(['status' => AiAnalysis::FAILED, 'error' => mb_substr($message, 0, 500), 'finished_at' => now()]);
    }
}
