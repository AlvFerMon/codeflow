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

    public function test_diff_batcher_skips_unreviewable_files_and_splits_batches(): void
    {
        $file = fn (string $path, int $lines, array $extra = []) => $extra + [
            'path' => $path, 'status' => 'modified', 'binary' => false, 'too_large' => false,
            'hunks' => [['lines' => array_map(fn ($i) => ['type' => 'add', 'old' => null, 'new' => $i, 'text' => str_repeat('x', 50)], range(1, $lines))]],
        ];

        $result = (new DiffBatcher(batchChars: 2000, maxFiles: 3, maxChars: 100000))->batch([
            $file('a.php', 20),
            $file('b.php', 20),
            $file('img.png', 1, ['binary' => true]),
            $file('old.php', 1, ['status' => 'deleted']),
            $file('c.php', 20),
            $file('d.php', 20), // pasa del máximo de archivos
        ]);

        $this->assertSame(3, $result['analyzed']);
        $this->assertSame(3, $result['skipped']);
        $this->assertSame([['a.php'], ['b.php'], ['c.php']], array_column($result['batches'], 'paths'));
        $this->assertStringContainsString('### archivo: a.php (modified)', $result['batches'][0]['text']);
        $this->assertStringContainsString('    1 + ', $result['batches'][0]['text']);
    }
}
