<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/** API que usa la CLI `codeflow` (clone / pull / push). */
class CliApiTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    public function test_push_with_an_old_base_commit_is_rejected_instead_of_overwriting(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $main = $ctx['main'];

        $base = $this->getJson("/api/branches/$main/manifest")->assertOk()->json('head_commit_id');

        // Alguien sube antes
        $this->postJson("/api/branches/$main/commits", [
            'message' => 'Otro cambio', 'changes' => [['path' => 'otro.txt', 'content' => 'x']],
        ])->assertCreated();

        // Mi push sobre el commit antiguo → 409 y nada se guarda
        $this->postJson("/api/branches/$main/commits", [
            'message' => 'Mi cambio', 'base_commit_id' => $base,
            'changes' => [['path' => 'mio.txt', 'content' => 'y']],
        ])->assertStatus(409)->assertJsonPath('message', fn ($m) => str_contains($m, 'Haz pull'));

        $paths = collect($this->getJson("/api/branches/$main/manifest")->json('files'))->pluck('path');
        $this->assertFalse($paths->contains('mio.txt'));

        // Con el commit actual sí entra
        $head = $this->getJson("/api/branches/$main/manifest")->json('head_commit_id');
        $this->postJson("/api/branches/$main/commits", [
            'message' => 'Mi cambio', 'base_commit_id' => $head,
            'changes' => [['path' => 'mio.txt', 'content' => 'y']],
        ])->assertCreated();
    }

    public function test_binary_files_round_trip_in_base64(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $main = $ctx['main'];
        $png = "\x89PNG\r\n\x1a\n\0\0\0\rIHDR".random_bytes(64);

        $this->postJson("/api/branches/$main/commits", [
            'message' => 'Logo', 'changes' => [['path' => 'img/logo.png', 'content' => base64_encode($png), 'encoding' => 'base64']],
        ])->assertCreated();

        $this->postJson("/api/branches/$main/commits", [
            'message' => 'Mal', 'changes' => [['path' => 'x.bin', 'content' => '###', 'encoding' => 'base64']],
        ])->assertStatus(422);

        $file = $this->getJson("/api/branches/$main/file?path=img/logo.png")->assertOk()->json();
        $this->assertSame('base64', $file['encoding']);
        $this->assertSame($png, base64_decode($file['content']));

        $manifest = collect($this->getJson("/api/branches/$main/manifest")->json('files'))->keyBy('path');
        $this->assertTrue($manifest['img/logo.png']['binary']);
        $this->assertSame(strlen($png), $manifest['img/logo.png']['size']);
        $this->assertFalse($manifest['README.md']['binary']);
    }

    public function test_manifest_and_batch_download_of_a_given_commit(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $main = $ctx['main'];

        $this->postJson("/api/branches/$main/commits", [
            'message' => 'v1', 'changes' => [['path' => 'src/a.php', 'content' => 'uno'], ['path' => 'src/b.php', 'content' => 'dos']],
        ])->assertCreated();
        $v1 = $this->getJson("/api/branches/$main/manifest")->json();

        $this->postJson("/api/branches/$main/commits", [
            'message' => 'v2', 'changes' => [['path' => 'src/a.php', 'content' => 'UNO'], ['path' => 'src/b.php', 'delete' => true]],
        ])->assertCreated();
        $v2 = $this->getJson("/api/branches/$main/manifest")->json();

        // El blob_id cambia solo en los archivos modificados
        $ids1 = collect($v1['files'])->pluck('blob_id', 'path');
        $ids2 = collect($v2['files'])->pluck('blob_id', 'path');
        $this->assertNotSame($ids1['src/a.php'], $ids2['src/a.php']);
        $this->assertSame($ids1['README.md'], $ids2['README.md']);
        $this->assertFalse($ids2->has('src/b.php'));

        // Manifiesto de un commit anterior
        $old = $this->getJson("/api/branches/$main/manifest?commit_id={$v1['head_commit_id']}")->assertOk()->json();
        $this->assertSame($v1['files'], $old['files']);
        $this->assertSame($v2['head_commit_id'], $old['head_commit_id']);
        $this->getJson("/api/branches/$main/manifest?commit_id=999999")->assertNotFound();

        // Descarga por lotes de un commit concreto (aunque la rama ya haya avanzado)
        $batch = $this->postJson("/api/branches/$main/files/batch", [
            'commit_id' => $v1['head_commit_id'], 'paths' => ['src/a.php', 'src/b.php'],
        ])->assertOk()->json();
        $this->assertSame(['uno', 'dos'], array_column($batch['files'], 'content'));
        $this->assertSame([], $batch['remaining']);

        $this->postJson("/api/branches/$main/files/batch", ['paths' => ['no/existe.txt']])->assertNotFound();
        $this->postJson("/api/branches/$main/files/batch", ['commit_id' => 999999, 'paths' => ['README.md']])->assertNotFound();
    }

    public function test_strangers_cannot_read_the_manifest_of_a_private_repository(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);

        $this->actingAsUser(User::factory()->create());
        $this->getJson("/api/branches/{$ctx['main']}/manifest")->assertNotFound();
        $this->postJson("/api/branches/{$ctx['main']}/files/batch", ['paths' => ['README.md']])->assertNotFound();
    }
}
