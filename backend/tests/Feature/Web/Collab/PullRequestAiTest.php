<?php

namespace Tests\Feature\Web\Collab;

use App\Ai\DiffBatcher;
use App\Jobs\RunPullRequestAnalysis;
use App\Models\AiAnalysis;
use App\Services\PullRequestAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Revisión de pull requests con IA (opcional), con Ollama simulado. */
class PullRequestAiTest extends TestCase
{
    use BuildsCollabFixtures, RefreshDatabase;

    private const SECRET_LINE = 'echo $claveSecretaDelCliente;';

    private function enableAi(): void
    {
        config([
            'codeflow.ai.enabled' => true,
            'codeflow.ai.url' => 'http://ollama.test:11434',
            'codeflow.ai.model' => 'qwen2.5-coder:3b',
            'codeflow.ai.queue_connection' => 'sync',
        ]);
    }

    private function fakeOllama(array $answer): void
    {
        Http::fake(['ollama.test:11434/api/chat' => Http::response(['message' => ['role' => 'assistant', 'content' => json_encode($answer)]])]);
    }

    private function prWithCode()
    {
        $this->buildCollabProject();

        return $this->makePullRequest($this->makeBranch('feature/login', 'src/Login.php', "<?php\n".self::SECRET_LINE."\n"));
    }

    public function test_without_ai_enabled_nothing_changes(): void
    {
        $pr = $this->prWithCode();

        $this->actAs($this->owner)->get(route('pulls.show', [$this->repo, $pr->number]))
            ->assertOk()->assertDontSee('Revisión con IA')->assertDontSee('Analizar con IA');
        $this->post(route('pulls.ai.store', [$this->repo, $pr->number]))->assertNotFound();
        $this->get(route('pulls.ai.show', [$this->repo, $pr->number]))->assertNotFound();
    }

    public function test_collaborators_can_request_an_analysis_and_observers_only_see_it(): void
    {
        $this->enableAi();
        config(['codeflow.ai.queue_connection' => 'database']);
        Queue::fake();
        $pr = $this->prWithCode();

        $this->actAs($this->observer)->get(route('pulls.show', [$this->repo, $pr->number]))
            ->assertOk()->assertSee('Revisión con IA')->assertDontSee('Analizar con IA');
        $this->post(route('pulls.ai.store', [$this->repo, $pr->number]))->assertForbidden();

        $this->actAs($this->dev)->get(route('pulls.show', [$this->repo, $pr->number]))->assertSee('Analizar con IA');
        $this->post(route('pulls.ai.store', [$this->repo, $pr->number]))->assertRedirect();

        $analysis = AiAnalysis::sole();
        $this->assertSame(AiAnalysis::PENDING, $analysis->status);
        $this->assertSame('qwen2.5-coder:3b', $analysis->model);
        Queue::assertPushedOn('ai', RunPullRequestAnalysis::class);

        // Mientras está en cola, la sección se vuelve a consultar sola y no se puede lanzar otro
        $this->get(route('pulls.ai.show', [$this->repo, $pr->number]))->assertOk()->assertSee('data-ai-poll', false)->assertDontSee('Analizar con IA');
        $this->post(route('pulls.ai.store', [$this->repo, $pr->number]));
        $this->assertSame(1, AiAnalysis::count());
    }

    public function test_analysis_stores_validated_findings_and_never_the_code(): void
    {
        $this->enableAi();
        $this->fakeOllama([
            'summary' => 'Añade el login. Imprime una variable sensible.',
            'findings' => [
                ['path' => 'src/Login.php', 'line' => 2, 'severity' => 'high', 'category' => 'security', 'title' => 'Se muestra una clave',
                    'explanation' => "Se imprime un secreto.\n```php\n".self::SECRET_LINE."\n```", 'suggestion' => 'No mostrar la variable.'],
                // Descartados: archivo que no está en el PR, gravedad inventada y sin explicación
                ['path' => 'otro/Archivo.php', 'line' => 1, 'severity' => 'high', 'category' => 'bug', 'title' => 'x', 'explanation' => 'x'],
                ['path' => 'src/Login.php', 'line' => 1, 'severity' => 'critica', 'category' => 'bug', 'title' => 'x', 'explanation' => 'x'],
                ['path' => 'src/Login.php', 'severity' => 'low', 'category' => 'style', 'title' => 'x', 'explanation' => ''],
            ],
        ]);
        $pr = $this->prWithCode();

        $this->actAs($this->dev)->post(route('pulls.ai.store', [$this->repo, $pr->number]))->assertRedirect();

        $analysis = AiAnalysis::sole();
        $this->assertSame(AiAnalysis::DONE, $analysis->status);
        $this->assertSame(1, $analysis->findings_count);
        $this->assertSame(1, $analysis->files_analyzed);

        $finding = $analysis->findings()->sole();
        $this->assertSame(['src/Login.php', 2, 'high', 'security'], [$finding->path, $finding->line, $finding->severity, $finding->category]);

        // El modelo recibió el diff con números de línea…
        Http::assertSent(fn (Request $r) => str_contains($r['messages'][1]['content'], '2 + '.self::SECRET_LINE) && $r['format']['required'] === ['summary', 'findings']);
        // …pero en la base de datos no queda ni una línea de código
        $stored = json_encode([DB::table('ai_analyses')->get(), DB::table('ai_findings')->get()]);
        $this->assertStringNotContainsString('claveSecretaDelCliente', $stored);

        $this->get(route('pulls.show', [$this->repo, $pr->number]))->assertOk()
            ->assertSee('Se muestra una clave')->assertSee('src/Login.php:2')->assertSee('Volver a analizar')->assertSee('fragmento de código omitido');
    }

    public function test_errors_are_shown_with_a_clear_message(): void
    {
        $this->enableAi();
        $pr = $this->prWithCode();

        Http::fake(['ollama.test:11434/*' => Http::response(['error' => 'model not found'], 404)]);
        $this->actAs($this->dev)->post(route('pulls.ai.store', [$this->repo, $pr->number]));
        $this->assertSame(AiAnalysis::FAILED, AiAnalysis::latest('id')->first()->status);
        $this->get(route('pulls.show', [$this->repo, $pr->number]))->assertSee('no está descargado')->assertSee('ollama pull qwen2.5-coder:3b');

        Http::fake(['ollama.test:11434/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('down')]);
        $this->post(route('pulls.ai.store', [$this->repo, $pr->number]));
        $this->assertStringContainsString('No se pudo conectar', AiAnalysis::latest('id')->first()->error);
    }

    public function test_a_stuck_analysis_does_not_block_a_new_one(): void
    {
        $this->enableAi();
        config(['codeflow.ai.queue_connection' => 'database']);
        Queue::fake();
        $pr = $this->prWithCode();

        $stuck = AiAnalysis::create(['pull_request_id' => $pr->id, 'status' => AiAnalysis::PENDING, 'model' => 'x']);
        $stuck->forceFill(['created_at' => now()->subHour()])->save();

        app(PullRequestAnalysisService::class)->request($pr, $this->dev);

        $this->assertSame(AiAnalysis::FAILED, $stuck->fresh()->status);
        $this->assertSame(2, AiAnalysis::count());
    }

    private static function fileDiff(string $path, int $lines, array $extra = []): array
    {
        return $extra + [
            'path' => $path, 'status' => 'modified', 'binary' => false, 'too_large' => false,
            'hunks' => [['lines' => array_map(fn ($i) => ['type' => 'add', 'old' => null, 'new' => $i, 'text' => str_repeat('x', 50)], range(1, $lines))]],
        ];
    }

    public function test_diff_batcher_skips_only_unreviewable_files_with_their_reason(): void
    {
        $result = (new DiffBatcher(batchChars: 2000, maxFiles: 3, maxChars: 100000))->batch([
            self::fileDiff('a.php', 20),
            self::fileDiff('b.php', 20),
            self::fileDiff('img.png', 1, ['binary' => true]),
            self::fileDiff('old.php', 1, ['status' => 'deleted']),
            self::fileDiff('package-lock.json', 30),
            self::fileDiff('node_modules/x/index.js', 30),
            self::fileDiff('public/build/app.min.js', 30),
            self::fileDiff('c.php', 20),
            self::fileDiff('d.php', 20), // pasa del máximo de archivos
        ]);

        $this->assertSame(3, $result['analyzed']);
        $this->assertSame(0, $result['truncated']);
        $this->assertSame([
            ['path' => 'img.png', 'reason' => 'binary'],
            ['path' => 'old.php', 'reason' => 'deleted'],
            ['path' => 'package-lock.json', 'reason' => 'generated'],
            ['path' => 'node_modules/x/index.js', 'reason' => 'generated'],
            ['path' => 'public/build/app.min.js', 'reason' => 'generated'],
            ['path' => 'd.php', 'reason' => 'limit'],
        ], $result['skipped']);
        $this->assertSame([['a.php'], ['b.php'], ['c.php']], array_column($result['batches'], 'paths'));
        $this->assertStringContainsString('### archivo: a.php (modified)', $result['batches'][0]['text']);
        $this->assertStringContainsString('    1 + ', $result['batches'][0]['text']);
    }

    public function test_diff_batcher_trims_big_files_instead_of_skipping_them(): void
    {
        // Antes: los archivos que no cabían en el presupuesto total se omitían (22 de 24 en un PR real)
        $files = [self::fileDiff('pequeno.php', 5)];
        foreach (range(1, 23) as $i) {
            $files[] = self::fileDiff("src/Grande{$i}.php", 800); // ~48 KB cada uno
        }

        $result = (new DiffBatcher(batchChars: 20000, maxFiles: 60, maxChars: 60000))->batch($files);

        $this->assertSame(24, $result['analyzed']);
        $this->assertSame([], $result['skipped']);
        $this->assertSame(23, $result['truncated']);

        $texts = implode('', array_column($result['batches'], 'text'));
        $this->assertLessThanOrEqual(60000, strlen($texts));
        // El pequeño entra entero; los grandes, recortados en un salto de línea y con aviso
        $this->assertStringContainsString("    5 + ", $texts);
        $this->assertStringContainsString('líneas más de este archivo sin revisar', $texts);
        foreach ($result['batches'] as $batch) {
            $this->assertLessThanOrEqual(20000, strlen($batch['text']));
        }
        $this->assertCount(24, array_merge(...array_column($result['batches'], 'paths')));
        $this->assertLessThanOrEqual(4, count($result['batches']));
    }

    public function test_progress_and_skipped_files_are_stored_and_a_bad_batch_does_not_fail_everything(): void
    {
        $this->enableAi();
        config(['codeflow.ai.batch_chars' => 300, 'codeflow.ai.max_chars' => 600]);
        Http::fakeSequence('ollama.test:11434/api/chat')
            ->push(['message' => ['content' => '{"summary": "corta', 'role' => 'assistant'], 'done_reason' => 'length'])
            ->push(['message' => ['role' => 'assistant', 'content' => json_encode(['summary' => 'Bien.', 'findings' => []])]]);

        $this->buildCollabProject();
        $branch = $this->makeBranch('feature/varios', 'src/A.php', "<?php\n".str_repeat("echo 1;\n", 30));
        app(\App\Services\RepositoryService::class)->commitChanges($branch->fresh(), $this->dev->fresh(), 'Más archivos', [
            ['path' => 'src/B.php', 'content' => "<?php\n".str_repeat("echo 2;\n", 30)],
            ['path' => 'composer.lock', 'content' => '{}'],
        ]);
        $pr = $this->makePullRequest($branch->fresh());

        $this->actAs($this->dev)->post(route('pulls.ai.store', [$this->repo, $pr->number]));

        $analysis = AiAnalysis::sole();
        $this->assertSame(AiAnalysis::DONE, $analysis->status);
        $this->assertSame([2, 2], [$analysis->batches_total, $analysis->batches_done]);
        $this->assertSame(2, $analysis->files_analyzed);
        $this->assertSame([['path' => 'composer.lock', 'reason' => 'generated']], $analysis->skipped_files);
        $this->assertStringContainsString('1 de 2 partes', $analysis->summary);
        Http::assertSent(fn (Request $r) => $r['keep_alive'] === '30m' && $r['options']['num_predict'] === 1024);

        $this->get(route('pulls.show', [$this->repo, $pr->number]))->assertOk()
            ->assertSee('Ver archivos omitidos')->assertSee('composer.lock')->assertSee('generado o dependencia');
    }
}
