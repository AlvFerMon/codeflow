<?php

namespace Tests\Feature\Vcs;

use App\Models\Blob;
use App\Models\Branch;
use App\Models\Commit;
use App\Models\Project;
use App\Models\Repository;
use App\Models\User;
use App\Services\VcsService;
use App\Vcs\Crypto\KeyManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EncryptionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::forceCreate(['name' => 'TFG', 'owner_id' => $this->user->id]);
    }

    /** @return array{0:Repository,1:Branch} */
    private function repository(string $name = 'app'): array
    {
        $repository = Repository::create(['project_id' => $this->project->id, 'name' => $name]);
        $branch = Branch::create(['repository_id' => $repository->id, 'name' => 'main', 'created_by' => $this->user->id]);

        return [$repository, $branch];
    }

    private function vcs(): VcsService
    {
        return app(VcsService::class);
    }

    public function test_creating_a_repository_stores_an_encrypted_key(): void
    {
        [$repository] = $this->repository();

        $raw = DB::table('repositories')->where('id', $repository->id)->first();

        $this->assertNotEmpty($raw->encrypted_key);
        $this->assertSame(1, (int) $raw->key_version);
        $this->assertSame('private', $raw->visibility);
        $this->assertSame(32, strlen(app(KeyManager::class)->decryptKey($raw->encrypted_key)));
        $this->assertArrayNotHasKey('encrypted_key', $repository->toArray());
        $this->assertFalse($repository->isPublic());
    }

    public function test_file_content_and_commit_message_are_encrypted_at_rest(): void
    {
        [$repository, $branch] = $this->repository();
        $secret = "<?php\n// contraseña súper secreta\n";

        $commit = $this->vcs()->commit($branch, $this->user, 'Mensaje confidencial', [
            ['path' => 'src/secreto.php', 'content' => $secret],
        ]);

        $rawBlob = DB::table('blobs')->where('repository_id', $repository->id)->first();
        $this->assertStringNotContainsString('contraseña', $rawBlob->content);
        $this->assertNotSame($secret, $rawBlob->content);
        $this->assertSame(64, strlen($rawBlob->content_mac));
        $this->assertSame(strlen($secret), (int) $rawBlob->size);

        $this->assertSame($secret, Blob::find($rawBlob->id)->content);

        $rawCommit = DB::table('commits')->where('id', $commit->id)->first();
        $this->assertStringNotContainsString('confidencial', $rawCommit->message);
        $this->assertSame('Mensaje confidencial', Commit::find($commit->id)->message);
        $this->assertSame('Mensaje confidencial', Commit::find($commit->id)->toArray()['message']);

        $this->assertSame(
            ['content' => $secret, 'size' => strlen($secret), 'binary' => false],
            $this->vcs()->readFile($repository->id, $rawBlob->id)
        );
    }

    public function test_commit_sha_is_a_verifiable_content_hash(): void
    {
        [, $branch] = $this->repository();

        $first = $this->vcs()->commit($branch, $this->user, 'uno', [['path' => 'a.txt', 'content' => 'a']]);
        $second = $this->vcs()->commit($branch, $this->user, 'dos', [['path' => 'b.txt', 'content' => 'b']]);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $second->sha);
        $this->assertNotSame($first->sha, $second->sha);
        $this->assertTrue($this->vcs()->verifyCommit(Commit::find($first->id)));
        $this->assertTrue($this->vcs()->verifyCommit(Commit::find($second->id)));

        // Si se altera el mensaje, el sha deja de cuadrar
        $tampered = Commit::find($second->id);
        $tampered->message = 'otro';
        $this->assertFalse($this->vcs()->verifyCommit($tampered));
    }

    public function test_blobs_are_deduplicated_per_repository_only(): void
    {
        [$repoA, $branchA] = $this->repository('a');
        [$repoB, $branchB] = $this->repository('b');

        $this->vcs()->commit($branchA, $this->user, 'c1', [['path' => 'x.txt', 'content' => 'igual']]);
        $this->vcs()->commit($branchA, $this->user, 'c2', [['path' => 'y.txt', 'content' => 'igual']]);
        $this->vcs()->commit($branchB, $this->user, 'c3', [['path' => 'x.txt', 'content' => 'igual']]);

        $this->assertSame(1, Blob::where('repository_id', $repoA->id)->count());
        $this->assertSame(1, Blob::where('repository_id', $repoB->id)->count());

        // Mismo contenido en repos distintos: MAC distinto (clave distinta)
        $macA = Blob::where('repository_id', $repoA->id)->value('content_mac');
        $macB = Blob::where('repository_id', $repoB->id)->value('content_mac');
        $this->assertNotSame($macA, $macB);
    }

    public function test_size_bytes_grows_only_with_new_blobs(): void
    {
        [$repository, $branch] = $this->repository();

        $this->vcs()->commit($branch, $this->user, 'c1', [['path' => 'a.txt', 'content' => '12345']]);
        $this->assertSame(5, $repository->fresh()->size_bytes);

        $this->vcs()->commit($branch, $this->user, 'c2', [['path' => 'b.txt', 'content' => '12345']]);
        $this->assertSame(5, $repository->fresh()->size_bytes);

        $this->vcs()->commit($branch, $this->user, 'c3', [['path' => 'c.txt', 'content' => 'abc']]);
        $this->assertSame(8, $repository->fresh()->size_bytes);
    }

    public function test_diff_with_hunks_has_the_expected_shape(): void
    {
        [$repository, $branch] = $this->repository();

        $base = $this->vcs()->commit($branch, $this->user, 'base', [
            ['path' => 'main.c', 'content' => "a\nb\nc\n}\n"],
            ['path' => 'borrar.txt', 'content' => "x\n"],
        ]);
        $head = $this->vcs()->commit($branch, $this->user, 'cambios', [
            ['path' => 'main.c', 'content' => "a\nB\nc\n}\n}\n"],
            ['path' => 'borrar.txt', 'delete' => true],
            ['path' => 'nuevo.txt', 'content' => "hola\n"],
        ]);

        $files = collect($this->vcs()->diff($base->id, $head->id, true))->keyBy('path');

        $this->assertSame(['borrar.txt', 'main.c', 'nuevo.txt'], $files->keys()->all());
        $this->assertSame('deleted', $files['borrar.txt']['status']);
        $this->assertSame([0, 1], [$files['borrar.txt']['additions'], $files['borrar.txt']['deletions']]);
        $this->assertSame('added', $files['nuevo.txt']['status']);
        $this->assertSame([1, 0], [$files['nuevo.txt']['additions'], $files['nuevo.txt']['deletions']]);

        $main = $files['main.c'];
        $this->assertSame('modified', $main['status']);
        $this->assertSame(2, $main['additions']); // "B" y la "}" repetida
        $this->assertSame(1, $main['deletions']);
        $this->assertFalse($main['binary']);
        $this->assertFalse($main['too_large']);
        $this->assertCount(1, $main['hunks']);
        $this->assertSame(['old_start', 'old_lines', 'new_start', 'new_lines', 'lines'], array_keys($main['hunks'][0]));
        $this->assertSame(
            ['type' => 'add', 'old' => null, 'new' => 2, 'text' => 'B'],
            collect($main['hunks'][0]['lines'])->firstWhere('type', 'add')
        );

        // Sin $withHunks no se incluye la clave "hunks"
        $this->assertArrayNotHasKey('hunks', $this->vcs()->diff($base->id, $head->id)[0]);

        // compare() desde la rama base = diff(merge-base, head)
        $this->assertEquals(
            $this->vcs()->diff($base->id, $head->id),
            $this->vcs()->compare($repository->id, $base->id, $head->id)
        );
        $this->assertCount(2, $this->vcs()->compare($repository->id, null, $head->id));
    }

    public function test_binary_content_is_detected_and_not_diffed(): void
    {
        [$repository, $branch] = $this->repository();
        $png = "\x89PNG\r\n\x1a\n\0\0\0\rIHDR".random_bytes(64);

        $commit = $this->vcs()->commit($branch, $this->user, 'imagen', [
            ['path' => 'logo.png', 'content' => $png],
            ['path' => 'latin1.txt', 'content' => "caf\xE9"],
        ]);

        $blobs = Blob::where('repository_id', $repository->id)->get();
        $this->assertTrue($blobs->every(fn (Blob $blob) => $blob->is_binary));
        $this->assertSame($png, $blobs->firstWhere('size', strlen($png))->content);

        $files = $this->vcs()->diff(null, $commit->id, true);
        foreach ($files as $file) {
            $this->assertTrue($file['binary']);
            $this->assertSame(0, $file['additions']);
            $this->assertSame([], $file['hunks']);
        }
    }

    public function test_files_bigger_than_the_limit_are_rejected(): void
    {
        config(['codeflow.limits.max_file_bytes' => 10]);
        [, $branch] = $this->repository();

        $this->expectException(HttpException::class);
        $this->vcs()->commit($branch, $this->user, 'grande', [['path' => 'a.txt', 'content' => str_repeat('x', 11)]]);
    }

    public function test_too_large_diffs_are_flagged(): void
    {
        config(['codeflow.limits.max_diff_lines' => 3]);
        [, $branch] = $this->repository();

        $commit = $this->vcs()->commit($branch, $this->user, 'largo', [['path' => 'a.txt', 'content' => "1\n2\n3\n4\n"]]);
        $file = $this->vcs()->diff(null, $commit->id, true)[0];

        $this->assertTrue($file['too_large']);
        $this->assertSame(4, $file['additions']);
        $this->assertSame([], $file['hunks']);
    }

    public function test_missing_master_key_fails_with_a_clear_message(): void
    {
        $this->app->forgetInstance(KeyManager::class);
        config(['codeflow.master_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CODEFLOW_MASTER_KEY');
        Repository::create(['project_id' => $this->project->id, 'name' => 'sin-clave']);
    }
}
