<?php

namespace Tests\Feature\Web\Code;

use App\Models\Blob;
use App\Models\Commit;
use App\Services\VcsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FilesTest extends TestCase
{
    use BuildsCodeFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFixtures();
    }

    /** Contenido actual de un archivo en main (o null si no existe). */
    private function fileContent(string $path, string $branch = 'main'): ?string
    {
        $tree = app(VcsService::class)->tree($this->branch($this->private, $branch)->head_commit_id);

        return isset($tree[$path]) ? Blob::find($tree[$path])->content : null;
    }

    private function commitsCount(): int
    {
        return Commit::where('repository_id', $this->private->id)->count();
    }

    public function test_observer_cannot_change_code(): void
    {
        $this->signIn($this->observer);
        $repo = $this->private;

        $this->get(route('repositories.show', $repo))->assertOk()->assertDontSee('Subir archivos');
        $this->get(route('files.create', [$repo, 'mode' => 'upload']))->assertForbidden();
        $this->get(route('files.edit', [$repo, 'path' => 'README.md']))->assertForbidden();
        $this->post(route('files.store', $repo), ['mode' => 'new', 'branch' => 'main', 'filename' => 'x.txt', 'file_content' => 'x'])->assertForbidden();
        $this->post(route('files.store', $repo), [
            'mode' => 'upload', 'branch' => 'main', 'files' => [UploadedFile::fake()->createWithContent('a.txt', 'a')],
        ])->assertForbidden();
        $this->delete(route('files.destroy', $repo), ['branch' => 'main', 'path' => 'README.md'])->assertForbidden();
        $this->post(route('branches.store', $repo), ['name' => 'nueva'])->assertForbidden();

        $this->assertSame(2, $this->commitsCount());
    }

    public function test_guest_is_redirected_to_login_for_writes(): void
    {
        $this->asGuestUser();
        $this->get(route('files.create', $this->public))->assertRedirect(route('login'));
        $this->post(route('files.store', $this->public), [])->assertRedirect(route('login'));
    }

    public function test_collaborator_uploads_multiple_files_encrypted_at_rest(): void
    {
        $this->signIn($this->collaborator);
        $repo = $this->private;

        $this->get(route('files.create', [$repo, 'mode' => 'upload', 'path' => 'assets']))->assertOk()->assertSee('Subir archivos');

        $this->post(route('files.store', $repo), [
            'mode' => 'upload',
            'branch' => 'main',
            'directory' => 'assets',
            'message' => 'Subir recursos',
            'files' => [
                UploadedFile::fake()->createWithContent('uno.txt', "Texto secreto número uno\n"),
                UploadedFile::fake()->createWithContent('dos.css', "body { color: red; }\n"),
            ],
        ])->assertRedirect(route('repositories.show', [$repo, 'branch' => 'main', 'path' => 'assets']))
            ->assertSessionHas('status');

        $this->assertSame(3, $this->commitsCount());
        $this->assertSame("Texto secreto número uno\n", $this->fileContent('assets/uno.txt'));
        $this->assertSame("body { color: red; }\n", $this->fileContent('assets/dos.css'));

        $commit = Commit::where('repository_id', $repo->id)->latest('id')->first();
        $this->assertSame('Subir recursos', $commit->message);
        $this->assertSame($this->collaborator->id, $commit->author_id);

        // Cifrado en reposo: ni el contenido ni el mensaje están en claro en la BD
        $tree = app(VcsService::class)->tree($commit->id);
        $raw = DB::table('blobs')->where('id', $tree['assets/uno.txt'])->value('content');
        $this->assertNotSame("Texto secreto número uno\n", $raw);
        $this->assertStringNotContainsString('secreto', (string) $raw);
        $this->assertStringNotContainsString('Subir recursos', (string) DB::table('commits')->where('id', $commit->id)->value('message'));
    }

    public function test_collaborator_creates_file_and_folder_edits_and_deletes(): void
    {
        $this->signIn($this->collaborator);
        $repo = $this->private;

        // Archivo nuevo (se conservan los espacios iniciales y el salto final; CRLF del navegador → LF)
        $this->get(route('files.create', [$repo, 'mode' => 'new']))->assertOk();
        $this->post(route('files.store', $repo), [
            'mode' => 'new', 'branch' => 'main', 'directory' => 'src', 'filename' => 'app.py',
            'file_content' => "    print('hola')\r\n", 'message' => '',
        ])->assertRedirect(route('repositories.blob', [$repo, 'branch' => 'main', 'path' => 'src/app.py']));
        $this->assertSame("    print('hola')\n", $this->fileContent('src/app.py'));
        $this->assertSame('Crear src/app.py', Commit::where('repository_id', $repo->id)->latest('id')->first()->message);

        // No se sobrescribe un archivo existente al crear
        $this->post(route('files.store', $repo), [
            'mode' => 'new', 'branch' => 'main', 'directory' => 'src', 'filename' => 'app.py', 'file_content' => 'x',
        ])->assertSessionHasErrors('filename');

        // Carpeta nueva → <carpeta>/.keep
        $this->post(route('files.store', $repo), ['mode' => 'folder', 'branch' => 'main', 'directory' => '', 'folder' => 'tests'])
            ->assertRedirect(route('repositories.show', [$repo, 'branch' => 'main', 'path' => 'tests']));
        $this->assertSame('', $this->fileContent('tests/.keep'));
        $this->get(route('repositories.show', [$repo, 'path' => 'tests']))->assertOk()->assertSee('Esta carpeta está vacía');

        // Editar (formulario precargado) y guardar
        $tree = app(VcsService::class)->tree($this->branch($repo)->head_commit_id);
        $this->get(route('files.edit', [$repo, 'branch' => 'main', 'path' => 'src/app.py']))->assertOk()
            ->assertSee("print(&#039;hola&#039;)", false);
        $this->post(route('files.store', $repo), [
            'mode' => 'edit', 'branch' => 'main', 'original_path' => 'src/app.py', 'original_blob' => $tree['src/app.py'],
            'directory' => 'src', 'filename' => 'app.py', 'file_content' => "print('adiós')\n", 'message' => 'Despedida',
        ])->assertRedirect(route('repositories.blob', [$repo, 'branch' => 'main', 'path' => 'src/app.py']));
        $this->assertSame("print('adiós')\n", $this->fileContent('src/app.py'));

        // Editar con una versión antigua: conflicto, no se pisa
        $this->post(route('files.store', $repo), [
            'mode' => 'edit', 'branch' => 'main', 'original_path' => 'src/app.py', 'original_blob' => $tree['src/app.py'],
            'directory' => 'src', 'filename' => 'app.py', 'file_content' => 'pisado',
        ])->assertSessionHasErrors('file_content');
        $this->assertSame("print('adiós')\n", $this->fileContent('src/app.py'));

        // Renombrar al editar
        $this->post(route('files.store', $repo), [
            'mode' => 'edit', 'branch' => 'main', 'original_path' => 'src/app.py',
            'directory' => 'src', 'filename' => 'main.py', 'file_content' => "print('adiós')\n",
        ])->assertRedirect();
        $this->assertNull($this->fileContent('src/app.py'));
        $this->assertSame("print('adiós')\n", $this->fileContent('src/main.py'));

        // Sin cambios: error del servicio como mensaje flash (no 500)
        $before = $this->commitsCount();
        $this->from(route('files.edit', [$repo, 'path' => 'src/main.py']))->post(route('files.store', $repo), [
            'mode' => 'edit', 'branch' => 'main', 'original_path' => 'src/main.py',
            'directory' => 'src', 'filename' => 'main.py', 'file_content' => "print('adiós')\n",
        ])->assertRedirect(route('files.edit', [$repo, 'path' => 'src/main.py']))->assertSessionHas('error', 'No hay cambios que confirmar.');
        $this->assertSame($before, $this->commitsCount());

        // Eliminar un archivo
        $this->delete(route('files.destroy', $repo), ['branch' => 'main', 'path' => 'src/main.py', 'message' => 'Fuera'])
            ->assertRedirect()->assertSessionHas('status');
        $this->assertNull($this->fileContent('src/main.py'));
        $this->assertSame('Fuera', Commit::where('repository_id', $repo->id)->latest('id')->first()->message);

        // Eliminar una carpeta entera
        $this->delete(route('files.destroy', $repo), ['branch' => 'main', 'path' => 'docs'])
            ->assertRedirect(route('repositories.show', [$repo, 'branch' => 'main']));
        $this->assertNull($this->fileContent('docs/README.md'));
        $this->assertNull($this->fileContent('docs/.keep'));
        $this->assertNotNull($this->fileContent('README.md'));

        $this->delete(route('files.destroy', $repo), ['branch' => 'main', 'path' => 'no-existe'])->assertNotFound();
    }

    public function test_invalid_paths_are_validation_errors(): void
    {
        $this->signIn($this->collaborator);

        $this->post(route('files.store', $this->private), [
            'mode' => 'new', 'branch' => 'main', 'directory' => 'src', 'filename' => '../../etc/passwd', 'file_content' => 'x',
        ])->assertSessionHasErrors('filename');

        $this->post(route('files.store', $this->private), [
            'mode' => 'upload', 'branch' => 'main', 'directory' => '../fuera',
            'files' => [UploadedFile::fake()->createWithContent('a.txt', 'a')],
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(2, $this->commitsCount());
    }

    public function test_size_limits_are_validated(): void
    {
        config(['codeflow.limits.max_file_bytes' => 2048]);
        $this->signIn($this->collaborator);

        $this->post(route('files.store', $this->private), [
            'mode' => 'upload', 'branch' => 'main',
            'files' => [UploadedFile::fake()->createWithContent('grande.txt', str_repeat('a', 4096))],
        ])->assertSessionHasErrors('files.0');

        $this->post(route('files.store', $this->private), [
            'mode' => 'new', 'branch' => 'main', 'filename' => 'grande.txt', 'file_content' => str_repeat('a', 3000),
        ])->assertSessionHasErrors('file_content');

        config(['codeflow.limits.max_files_per_commit' => 2]);
        $this->post(route('files.store', $this->private), [
            'mode' => 'upload', 'branch' => 'main',
            'files' => array_map(fn ($i) => UploadedFile::fake()->createWithContent("f{$i}.txt", "$i"), range(1, 3)),
        ])->assertSessionHasErrors('files');

        $this->assertSame(2, $this->commitsCount());
    }

    public function test_commits_on_other_branches(): void
    {
        $this->signIn($this->collaborator);
        $this->post(route('branches.store', $this->private), ['name' => 'develop'])->assertRedirect();

        $this->post(route('files.store', $this->private), [
            'mode' => 'new', 'branch' => 'develop', 'filename' => 'solo-develop.txt', 'file_content' => "hola\n",
        ])->assertRedirect(route('repositories.blob', [$this->private, 'branch' => 'develop', 'path' => 'solo-develop.txt']));

        $this->assertSame("hola\n", $this->fileContent('solo-develop.txt', 'develop'));
        $this->assertNull($this->fileContent('solo-develop.txt'));
        $this->get(route('repositories.show', [$this->private, 'branch' => 'develop']))->assertOk()->assertSee('solo-develop.txt');
        $this->get(route('repositories.show', $this->private))->assertOk()->assertDontSee('solo-develop.txt');
    }
}
