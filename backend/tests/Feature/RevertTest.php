<?php

namespace Tests\Feature;

use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/** Deshacer un commit (revert) con un commit nuevo. */
class RevertTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    private function files(int $branch): array
    {
        return collect($this->getJson("/api/branches/$branch/manifest")->json('files'))->pluck('path')->all();
    }

    private function content(int $branch, string $path): ?string
    {
        $r = $this->getJson("/api/branches/$branch/file?path=".urlencode($path));

        return $r->status() === 200 ? $r->json('content') : null;
    }

    public function test_revert_restores_modified_added_and_deleted_files(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $main = $ctx['main'];

        $this->postJson("/api/branches/$main/commits", ['message' => 'Base', 'changes' => [
            ['path' => 'a.txt', 'content' => 'uno'], ['path' => 'b.txt', 'content' => 'bee'],
        ]])->assertCreated();

        $bad = $this->postJson("/api/branches/$main/commits", ['message' => "Cambio malo\ncon detalle", 'changes' => [
            ['path' => 'a.txt', 'content' => 'roto'], ['path' => 'nuevo.txt', 'content' => 'x'], ['path' => 'b.txt', 'delete' => true],
        ]])->assertCreated()->json('id');

        // Otro cambio posterior en un archivo que no tocó el commit malo
        $this->postJson("/api/branches/$main/commits", ['message' => 'Otro', 'changes' => [['path' => 'c.txt', 'content' => 'ce']]])->assertCreated();

        $revert = $this->postJson("/api/commits/$bad/revert", ['branch_id' => $main])->assertCreated();
        $this->assertStringStartsWith('Deshace «Cambio malo»', $revert->json('message'));

        $this->assertSame('uno', $this->content($main, 'a.txt'));
        $this->assertSame('bee', $this->content($main, 'b.txt'));
        $this->assertNull($this->content($main, 'nuevo.txt'));
        $this->assertSame('ce', $this->content($main, 'c.txt'));

        // Deshacerlo otra vez no tiene sentido
        $this->postJson("/api/commits/$bad/revert", ['branch_id' => $main])->assertStatus(422);
        $this->assertTrue(app(\App\Services\VcsService::class)->verifyCommit(Commit::findOrFail($revert->json('id'))));
    }

    public function test_conflicts_are_reported_and_nothing_changes(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $main = $ctx['main'];

        $bad = $this->postJson("/api/branches/$main/commits", ['message' => 'Toca a', 'changes' => [['path' => 'a.txt', 'content' => '1']]])->json('id');
        $this->postJson("/api/branches/$main/commits", ['message' => 'Vuelve a tocar a', 'changes' => [['path' => 'a.txt', 'content' => '2']]])->assertCreated();
        $head = $this->getJson("/api/branches/$main/manifest")->json('head_commit_id');

        $this->postJson("/api/commits/$bad/revert", ['branch_id' => $main])->assertStatus(409)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'a.txt'));
        $this->assertSame($head, $this->getJson("/api/branches/$main/manifest")->json('head_commit_id'));
    }

    public function test_commit_must_be_in_the_branch_and_permissions_apply(): void
    {
        $owner = User::factory()->create();
        $observer = User::factory()->create();
        $ctx = $this->buildProject($owner, [[$observer, 'observer']]);
        $main = $ctx['main'];

        $other = $this->postJson("/api/repositories/{$ctx['repo']}/branches", ['name' => 'otra'])->assertCreated()->json('id');
        $onlyOther = $this->postJson("/api/branches/$other/commits", ['message' => 'Solo en otra', 'changes' => [['path' => 'o.txt', 'content' => 'o']]])->json('id');

        $this->postJson("/api/commits/$onlyOther/revert", ['branch_id' => $main])->assertStatus(422);

        $this->actingAsUser($observer);
        $this->postJson("/api/commits/$onlyOther/revert", ['branch_id' => $other])->assertForbidden();
    }

    public function test_web_button(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $sha = $this->postJson("/api/branches/{$ctx['main']}/commits", ['message' => 'Quitar', 'changes' => [['path' => 'q.txt', 'content' => 'q']]])->json('sha');
        $repo = Repository::find($ctx['repo']);

        $this->actingAs($owner)->get(route('commits.show', [$repo, $sha]))->assertOk()->assertSee('Deshacer este commit');
        $this->actingAs($owner)->post(route('commits.revert', [$repo, $sha]), ['branch' => 'main'])
            ->assertRedirect()->assertSessionHas('status');
        $this->actingAs($owner)->post(route('commits.revert', [$repo, $sha]), ['branch' => 'main'])->assertSessionHas('error');

        $this->actingAs($owner)->get(route('activity.index'))->assertOk()->assertSee('deshizo el commit');
    }
}
