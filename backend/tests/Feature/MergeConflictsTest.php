<?php

namespace Tests\Feature;

use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/** Fusión por líneas (3 vías) y resolución de conflictos de un Pull Request. */
class MergeConflictsTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    private array $ctx;

    private int $feature;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->ctx = $this->buildProject($this->owner);
        $main = $this->ctx['main'];

        $this->postJson("/api/branches/$main/commits", ['message' => 'Base', 'changes' => [
            ['path' => 'app.txt', 'content' => "uno\ndos\ntres\ncuatro\ncinco\n"],
            ['path' => 'logo.bin', 'content' => "\x00\x01base"],
        ]])->assertCreated();
        $this->feature = $this->postJson("/api/repositories/{$this->ctx['repo']}/branches", ['name' => 'feature', 'from_branch_id' => $main])->assertCreated()->json('id');
    }

    private User $owner;

    private function commit(int $branch, array $changes): void
    {
        $this->postJson("/api/branches/$branch/commits", ['message' => 'Cambio', 'changes' => $changes])->assertCreated();
    }

    private function openPr(): int
    {
        return $this->postJson("/api/repositories/{$this->ctx['repo']}/pull-requests", [
            'title' => 'Feature', 'source_branch_id' => $this->feature, 'target_branch_id' => $this->ctx['main'],
        ])->assertCreated()->json('id');
    }

    private function content(int $branch, string $path): ?string
    {
        $r = $this->getJson("/api/branches/$branch/file?path=".urlencode($path));

        return $r->status() === 200 ? $r->json('content') : null;
    }

    public function test_changes_in_different_lines_of_the_same_file_merge_automatically(): void
    {
        $this->commit($this->feature, [['path' => 'app.txt', 'content' => "UNO\ndos\ntres\ncuatro\ncinco\n"]]);
        $this->commit($this->ctx['main'], [['path' => 'app.txt', 'content' => "uno\ndos\ntres\ncuatro\nCINCO\n"]]);
        $pr = $this->openPr();

        $this->getJson("/api/pull-requests/$pr")->assertOk()
            ->assertJsonPath('mergeable', true)->assertJsonPath('auto_merged', ['app.txt']);

        $this->postJson("/api/pull-requests/$pr/merge")->assertSuccessful();
        $this->assertSame("UNO\ndos\ntres\ncuatro\nCINCO\n", $this->content($this->ctx['main'], 'app.txt'));
    }

    public function test_real_conflicts_are_resolved_with_a_merge_commit_in_the_source_branch(): void
    {
        $this->commit($this->feature, [['path' => 'app.txt', 'content' => "uno\nDOS-feature\ntres\ncuatro\ncinco\n"], ['path' => 'logo.bin', 'content' => "\x00\x01feature"]]);
        $this->commit($this->ctx['main'], [['path' => 'app.txt', 'content' => "uno\nDOS-main\ntres\ncuatro\ncinco\n"], ['path' => 'logo.bin', 'content' => "\x00\x01main"]]);
        $pr = $this->openPr();

        $this->getJson("/api/pull-requests/$pr")->assertJsonPath('mergeable', false)->assertJsonPath('conflicts', ['app.txt', 'logo.bin']);

        $files = collect($this->getJson("/api/pull-requests/$pr/conflicts")->assertOk()->json('files'))->keyBy('path');
        $this->assertSame('modified_both', $files['app.txt']['kind']);
        $this->assertStringContainsString("<<<<<<< feature (origen)\nDOS-feature\n=======\nDOS-main\n>>>>>>> main (destino)", $files['app.txt']['marked']);
        $this->assertTrue($files['logo.bin']['binary']);
        $this->assertNull($files['logo.bin']['marked']);

        // Con marcas sin quitar no se acepta; falta un archivo, tampoco
        $this->postJson("/api/pull-requests/$pr/resolve", ['files' => [
            ['path' => 'app.txt', 'action' => 'edit', 'content' => $files['app.txt']['marked']],
            ['path' => 'logo.bin', 'action' => 'theirs'],
        ]])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'marcas'));
        $this->postJson("/api/pull-requests/$pr/resolve", ['files' => [['path' => 'app.txt', 'action' => 'ours']]])->assertStatus(422);

        // Cabezas antiguas → 409
        $this->postJson("/api/pull-requests/$pr/resolve", ['source_head' => 1, 'files' => [
            ['path' => 'app.txt', 'action' => 'ours'], ['path' => 'logo.bin', 'action' => 'ours'],
        ]])->assertStatus(409);

        $this->postJson("/api/pull-requests/$pr/resolve", ['files' => [
            ['path' => 'app.txt', 'action' => 'edit', 'content' => "uno\nDOS-juntos\ntres\ncuatro\ncinco\n"],
            ['path' => 'logo.bin', 'action' => 'theirs'],
        ]])->assertCreated()->assertJsonPath('message', fn ($m) => str_starts_with($m, "Merge branch 'main' into feature"));

        $this->getJson("/api/pull-requests/$pr")->assertJsonPath('mergeable', true);
        $this->postJson("/api/pull-requests/$pr/merge")->assertSuccessful();
        $this->assertSame("uno\nDOS-juntos\ntres\ncuatro\ncinco\n", $this->content($this->ctx['main'], 'app.txt'));
        $this->assertSame(base64_encode("\x00\x01main"), $this->getJson("/api/branches/{$this->ctx['main']}/file?path=logo.bin")->json('content'));
    }

    public function test_deleted_on_one_side_and_modified_on_the_other(): void
    {
        $this->commit($this->feature, [['path' => 'app.txt', 'delete' => true]]);
        $this->commit($this->ctx['main'], [['path' => 'app.txt', 'content' => "uno\nCAMBIADO\n"]]);
        $pr = $this->openPr();

        $files = $this->getJson("/api/pull-requests/$pr/conflicts")->json('files');
        $this->assertSame('deleted_ours', $files[0]['kind']);

        $this->postJson("/api/pull-requests/$pr/resolve", ['files' => [['path' => 'app.txt', 'action' => 'delete']]])->assertCreated();
        $this->postJson("/api/pull-requests/$pr/merge")->assertSuccessful();
        $this->assertNull($this->content($this->ctx['main'], 'app.txt'));
    }

    public function test_web_page_and_permissions(): void
    {
        $observer = User::factory()->create();
        $this->postJson("/api/projects/{$this->ctx['project']}/members", ['email' => $observer->email, 'role' => 'observer'])->assertCreated();
        $this->commit($this->feature, [['path' => 'app.txt', 'content' => "uno\nA\n"]]);
        $this->commit($this->ctx['main'], [['path' => 'app.txt', 'content' => "uno\nB\n"]]);
        $pr = PullRequest::findOrFail($this->openPr());
        $repo = Repository::find($this->ctx['repo']);

        $this->actingAs($this->owner)->get(route('pulls.show', [$repo, $pr->number]))->assertOk()->assertSee('Resolver conflictos');
        $this->actingAs($this->owner)->get(route('pulls.conflicts', [$repo, $pr->number]))->assertOk()
            ->assertSee('&lt;&lt;&lt;&lt;&lt;&lt;&lt; feature (origen)', false);

        // Desde el navegador llega con \r\n y se guarda con \n (el fin de línea del archivo)
        $this->actingAs($this->owner)->post(route('pulls.resolve', [$repo, $pr->number]), ['files' => [
            ['path' => 'app.txt', 'action' => 'edit', 'eol' => 'lf', 'content' => "uno\r\nA y B\r\n"],
        ]])->assertRedirect(route('pulls.show', [$repo, $pr->number]))->assertSessionHas('status');
        $this->assertSame("uno\nA y B\n", $this->content($this->feature, 'app.txt'));

        $this->actingAs($this->owner)->get(route('pulls.conflicts', [$repo, $pr->number]))->assertRedirect(route('pulls.show', [$repo, $pr->number]));

        $this->actingAsUser($observer);
        $this->getJson("/api/pull-requests/{$pr->id}/conflicts")->assertForbidden();
    }
}
