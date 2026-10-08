<?php

namespace Tests\Feature\Web\Code;

use App\Models\Commit;
use App\Services\RepositoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowseCodeTest extends TestCase
{
    use BuildsCodeFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFixtures();
    }

    public function test_guest_can_browse_a_public_repository(): void
    {
        $this->asGuestUser();
        $repo = $this->public;
        $sha = Commit::where('repository_id', $repo->id)->value('sha');

        $this->get(route('repositories.show', $repo))->assertOk()->assertSee('README.md')->assertSee('Acerca de')
            ->assertDontSee('Subir archivos')->assertDontSee('Nuevo archivo');
        $this->get(route('repositories.blob', [$repo, 'path' => 'README.md']))->assertOk()->assertSee('# web')
            ->assertDontSee('Editar');
        $this->get(route('repositories.raw', [$repo, 'path' => 'README.md']))->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('commits.index', $repo))->assertOk()->assertSee('Initial commit');
        $this->get(route('commits.show', [$repo, $sha]))->assertOk()->assertSee('README.md');
        $this->get(route('branches.index', $repo))->assertOk()->assertSee('main')->assertDontSee('Crear rama');
    }

    public function test_private_repository_is_404_for_guests_and_non_members(): void
    {
        $repo = $this->private;
        $sha = Commit::where('repository_id', $repo->id)->value('sha');
        $urls = [
            route('repositories.show', $repo),
            route('repositories.blob', [$repo, 'path' => 'README.md']),
            route('repositories.raw', [$repo, 'path' => 'README.md']),
            route('commits.index', $repo),
            route('commits.show', [$repo, $sha]),
            route('branches.index', $repo),
        ];

        $this->asGuestUser();
        foreach ($urls as $url) {
            $this->get($url)->assertNotFound();
        }

        $this->signIn($this->outsider);
        foreach ($urls as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_tree_lists_folders_first_hides_keep_files_and_renders_readme(): void
    {
        $this->signIn($this->observer);

        $response = $this->get(route('repositories.show', $this->private))->assertOk();
        $response->assertSeeInOrder(['docs', 'src', 'README.md']);
        $response->assertSee('Estructura inicial');

        $docs = $this->get(route('repositories.show', [$this->private, 'path' => 'docs']))->assertOk();
        $docs->assertDontSee('.keep')->assertSee('<strong>importante</strong>', false);

        $this->get(route('repositories.show', [$this->private, 'path' => 'nope']))->assertNotFound();
        $this->get(route('repositories.show', [$this->private, 'branch' => 'nope']))->assertNotFound();
        // Una ruta de archivo en el explorador redirige a la vista del archivo
        $this->get(route('repositories.show', [$this->private, 'path' => 'README.md']))
            ->assertRedirect(route('repositories.blob', [$this->private, 'branch' => 'main', 'path' => 'README.md']));
    }

    public function test_readme_markdown_escapes_html(): void
    {
        app(RepositoryService::class)->commitChanges($this->branch($this->public), $this->owner, 'XSS', [
            ['path' => 'README.md', 'content' => "# Hola\n\n<script>alert(1)</script>\n\n[x](javascript:alert(1))\n"],
        ]);

        $this->asGuestUser();
        $this->get(route('repositories.show', $this->public))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href="javascript:', false)
            ->assertSee('&lt;script&gt;', false);

        $this->get(route('repositories.blob', [$this->public, 'path' => 'README.md', 'view' => 'rendered']))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('<h1>Hola</h1>', false);
    }

    public function test_blob_shows_lines_and_raw_download_headers(): void
    {
        $this->signIn($this->observer);
        $path = 'src/Controllers/UserController.php';

        $this->get(route('repositories.blob', [$this->private, 'path' => $path]))->assertOk()
            ->assertSee('class UserController {}')->assertSee('id="L3"', false)
            ->assertDontSee('Editar');

        $raw = $this->get(route('repositories.raw', [$this->private, 'path' => $path]))->assertOk();
        $this->assertSame("<?php\n\nclass UserController {}\n", $raw->getContent());
        $this->assertStringStartsWith('text/plain', $raw->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline', $raw->headers->get('Content-Disposition'));

        $download = $this->get(route('repositories.raw', [$this->private, 'path' => $path, 'download' => 1]))->assertOk();
        $this->assertStringStartsWith('attachment', $download->headers->get('Content-Disposition'));
        $this->assertStringContainsString('UserController.php', $download->headers->get('Content-Disposition'));

        $this->get(route('repositories.blob', [$this->private, 'path' => 'no-existe.txt']))->assertNotFound();
    }

    public function test_binary_files_are_downloaded_not_rendered(): void
    {
        app(RepositoryService::class)->commitChanges($this->branch($this->private), $this->owner, 'Imagen', [
            ['path' => 'logo.bin', 'content' => "\x89PNG\0\x01\x02\xff"],
        ]);
        $this->signIn($this->collaborator);

        $this->get(route('repositories.blob', [$this->private, 'path' => 'logo.bin']))->assertOk()->assertSee('Archivo binario');
        $raw = $this->get(route('repositories.raw', [$this->private, 'path' => 'logo.bin']))->assertOk();
        $this->assertSame('application/octet-stream', $raw->headers->get('Content-Type'));
        $this->assertSame("\x89PNG\0\x01\x02\xff", $raw->getContent());
        $this->get(route('files.edit', [$this->private, 'branch' => 'main', 'path' => 'logo.bin']))
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_commit_history_and_detail_with_diff_and_sha_prefix(): void
    {
        app(RepositoryService::class)->commitChanges($this->branch($this->private), $this->collaborator, 'Cambiar controlador', [
            ['path' => 'src/Controllers/UserController.php', 'content' => "<?php\n\nclass UserController\n{\n}\n"],
        ]);
        $commit = Commit::where('repository_id', $this->private->id)->latest('id')->first();

        $this->signIn($this->observer);
        $this->get(route('commits.index', $this->private))->assertOk()
            ->assertSeeInOrder(['Cambiar controlador', 'Estructura inicial', 'Initial commit'])
            ->assertSee(substr($commit->sha, 0, 7));

        // Historial de un archivo: solo los commits que lo cambiaron
        $this->get(route('commits.index', [$this->private, 'path' => 'src/Controllers/UserController.php']))->assertOk()
            ->assertSee('Cambiar controlador')->assertSee('Estructura inicial')->assertDontSee('Initial commit');

        foreach ([$commit->sha, substr($commit->sha, 0, 7), strtoupper(substr($commit->sha, 0, 10))] as $sha) {
            $this->get(route('commits.show', [$this->private, $sha]))->assertOk()
                ->assertSee('Cambiar controlador')
                ->assertSee('src/Controllers/UserController.php')
                ->assertSee('modificado')
                ->assertSee('class UserController {}');
        }

        $this->get(route('commits.show', [$this->private, substr($commit->sha, 0, 6)]))->assertNotFound();
        $this->get(route('commits.show', [$this->private, 'zzzzzzz']))->assertNotFound();
        $this->get(route('commits.show', [$this->private, '0000000']))->assertNotFound();
        // Un commit de otro repositorio no se encuentra aquí
        $other = Commit::where('repository_id', $this->public->id)->value('sha');
        $this->get(route('commits.show', [$this->private, $other]))->assertNotFound();
    }
}
