<?php

namespace Tests\Feature\Web\Code;

use App\Models\Branch;
use App\Services\VcsService;
use App\Support\DiffSelection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Web\Account\CreatesProjects;
use Tests\TestCase;

/** R01: los commits grandes muestran la lista de archivos y cargan cada diff bajo demanda. */
class LargeDiffTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    public function test_big_commit_renders_first_files_and_lazy_loads_the_rest(): void
    {
        $this->setUpTeam();
        $repo = $this->makeRepository($this->project, $this->owner, 'grande', 'public');
        $branch = Branch::where('repository_id', $repo->id)->where('name', 'main')->firstOrFail();

        $changes = [];
        for ($i = 1; $i <= 30; $i++) {
            $changes[] = ['path' => sprintf('src/archivo%02d.txt', $i), 'content' => "linea unica {$i}\n".str_repeat("contenido\n", 50)];
        }
        // Un archivo con muchas líneas para comprobar el límite por archivo
        $changes[] = ['path' => 'a-largo.txt', 'content' => implode("\n", array_map(fn ($n) => "fila {$n}", range(1, 900)))."\n"];
        $commit = app(VcsService::class)->commit($branch, $this->owner, 'Commit enorme', $changes);

        $html = $this->get(route('commits.show', [$repo, $commit->sha]))->assertOk()
            ->assertSee('31 archivos cambiados')
            ->assertSee('src/archivo30.txt')                 // todos los archivos aparecen en la lista
            ->assertSee('se cargan al desplegarlos')
            ->getContent();

        // Solo los primeros se pintan; el resto llevan la URL de carga bajo demanda
        $this->assertSame(DiffSelection::EAGER_FILES, preg_match_all('/<details\s+open/', $html));
        $this->assertStringContainsString('data-diff-src="'.route('commits.diff', [$repo, $commit->sha]).'?path=src%2Farchivo30.txt"', $html);
        $this->assertStringNotContainsString('linea unica 30', $html);

        // Fragmento de un archivo (petición fetch)
        $this->get(route('commits.diff', [$repo, $commit->sha, 'path' => 'src/archivo30.txt']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('linea unica 30')->assertDontSee('<html', false);

        // Archivo largo: 400 líneas y enlace para ver el resto
        $this->get(route('commits.diff', [$repo, $commit->sha, 'path' => 'a-largo.txt']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('fila 300')->assertDontSee('fila 899')->assertSee('líneas restantes');
        $this->get(route('commits.diff', [$repo, $commit->sha, 'path' => 'a-largo.txt', 'full' => 1]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('fila 899');

        // Abierto directamente: página completa; ruta inexistente: 404
        $this->get(route('commits.diff', [$repo, $commit->sha, 'path' => 'src/archivo01.txt']))->assertOk()->assertSee('<html', false);
        $this->get(route('commits.diff', [$repo, $commit->sha, 'path' => 'no-existe.txt']))->assertNotFound();
    }
}
