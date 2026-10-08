<?php

namespace Tests\Feature\Web\Code;

use App\Models\Blob;
use App\Services\VcsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FolderUploadTest extends TestCase
{
    use BuildsCodeFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFixtures();
    }

    private function tree(): array
    {
        return app(VcsService::class)->tree($this->branch($this->private)->head_commit_id);
    }

    public function test_upload_page_offers_files_and_folders_with_limits(): void
    {
        config(['codeflow.limits.max_files_per_commit' => 1000]);
        $this->signIn($this->collaborator);

        $this->get(route('files.create', [$this->private, 'mode' => 'upload']))
            ->assertOk()
            ->assertSee('Seleccionar carpeta')
            ->assertSee('webkitdirectory', false)
            ->assertSee('Arrastra aquí archivos o carpetas completas');
    }

    public function test_folder_upload_keeps_the_structure(): void
    {
        $this->signIn($this->collaborator);

        $this->post(route('files.store', $this->private), [
            'mode' => 'upload',
            'branch' => 'main',
            'directory' => 'proyectos',
            'message' => 'Subir carpeta miapp',
            'files' => [
                UploadedFile::fake()->createWithContent('Main.php', '<?php // main'),
                UploadedFile::fake()->createWithContent('Util.php', '<?php // util'),
                UploadedFile::fake()->createWithContent('README.md', '# miapp'),
            ],
            'paths' => ['miapp/src/Main.php', 'miapp/src/helpers/Util.php', 'miapp/README.md'],
        ])->assertRedirect();

        $tree = $this->tree();
        $this->assertArrayHasKey('proyectos/miapp/src/Main.php', $tree);
        $this->assertArrayHasKey('proyectos/miapp/src/helpers/Util.php', $tree);
        $this->assertSame('# miapp', Blob::find($tree['proyectos/miapp/README.md'])->content);
    }

    public function test_upload_without_paths_uses_file_names(): void
    {
        $this->signIn($this->collaborator);

        $this->post(route('files.store', $this->private), [
            'mode' => 'upload', 'branch' => 'main',
            'files' => [UploadedFile::fake()->createWithContent('notas.txt', 'hola')],
        ])->assertRedirect();

        $this->assertArrayHasKey('notas.txt', $this->tree());
    }

    public function test_paths_cannot_escape_the_repository(): void
    {
        $this->signIn($this->collaborator);
        $before = $this->tree();

        $this->from(route('files.create', [$this->private, 'mode' => 'upload']))->post(route('files.store', $this->private), [
            'mode' => 'upload', 'branch' => 'main',
            'files' => [UploadedFile::fake()->createWithContent('x.txt', 'x')],
            'paths' => ['../../etc/x.txt'],
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame($before, $this->tree());
    }

    public function test_total_upload_size_is_limited(): void
    {
        config(['codeflow.limits.max_upload_bytes' => 1024]);
        $this->signIn($this->collaborator);

        $this->post(route('files.store', $this->private), [
            'mode' => 'upload', 'branch' => 'main',
            'files' => [
                UploadedFile::fake()->createWithContent('a.txt', str_repeat('a', 800)),
                UploadedFile::fake()->createWithContent('b.txt', str_repeat('b', 800)),
            ],
        ])->assertSessionHasErrors('files');

        $this->assertArrayNotHasKey('a.txt', $this->tree());
    }

    public function test_ajax_upload_returns_json_with_redirect_and_flash(): void
    {
        $this->signIn($this->collaborator);

        $response = $this->postJson(route('files.store', $this->private), [
            'mode' => 'upload', 'branch' => 'main',
            'files' => [UploadedFile::fake()->createWithContent('a.txt', 'a')],
            'paths' => ['docs/a.txt'],
        ]);

        $response->assertOk()->assertJsonStructure(['redirect', 'message']);
        $this->assertArrayHasKey('docs/a.txt', $this->tree());
        $this->get($response->json('redirect'))->assertSee('Cambios guardados');
    }

    public function test_ajax_upload_errors_are_json(): void
    {
        config(['codeflow.limits.max_files_per_commit' => 1]);
        $this->signIn($this->collaborator);

        $this->postJson(route('files.store', $this->private), [
            'mode' => 'upload', 'branch' => 'main',
            'files' => [
                UploadedFile::fake()->createWithContent('a.txt', 'a'),
                UploadedFile::fake()->createWithContent('b.txt', 'b'),
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('files');

        // Error del servicio (ruta no válida) también en JSON y sin dejar el mensaje para la página siguiente
        $this->postJson(route('files.store', $this->private), [
            'mode' => 'upload', 'branch' => 'main',
            'files' => [UploadedFile::fake()->createWithContent('a.txt', 'a')],
            'paths' => ['../a.txt'],
        ])->assertStatus(422)->assertJsonStructure(['message']);
        $this->assertNull(session('error'));
    }

    public function test_many_files_in_one_upload(): void
    {
        $this->signIn($this->collaborator);
        // El límite efectivo es el menor entre la app y max_file_uploads de PHP (20 en la CLI de tests, 1000 en Docker)
        $count = min(60, (int) ini_get('max_file_uploads') ?: 20);
        $files = [];
        $paths = [];
        for ($i = 0; $i < $count; $i++) {
            $files[] = UploadedFile::fake()->createWithContent("f{$i}.txt", "contenido {$i}");
            $paths[] = 'lote/sub'.($i % 4)."/f{$i}.txt";
        }

        $this->post(route('files.store', $this->private), [
            'mode' => 'upload', 'branch' => 'main', 'files' => $files, 'paths' => $paths,
        ])->assertRedirect();

        $tree = $this->tree();
        $this->assertCount($count, array_filter(array_keys($tree), fn ($p) => str_starts_with($p, 'lote/')));
    }
}
