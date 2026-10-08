<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

class VisibilityTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    private User $owner;

    private array $ctx;

    private int $issue;

    private int $pr;

    private int $commit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->ctx = $this->buildProject($this->owner);

        $this->issue = $this->postJson("/api/repositories/{$this->ctx['repo']}/issues", ['title' => 'Bug'])->assertCreated()->json('id');
        $this->postJson("/api/issues/{$this->issue}/comments", ['body' => 'Hola'])->assertCreated();
        [, $this->pr] = $this->openPullRequest($this->ctx);
        $this->commit = $this->getJson("/api/repositories/{$this->ctx['repo']}/commits")->json('data.0.id');
    }

    /** @return array<int,string> */
    private function readEndpoints(): array
    {
        ['repo' => $repo, 'main' => $main] = $this->ctx;

        return [
            "/api/repositories/$repo",
            "/api/repositories/$repo/branches",
            "/api/branches/$main/files",
            "/api/branches/$main/file?path=README.md",
            "/api/branches/$main/commits",
            "/api/repositories/$repo/commits",
            "/api/commits/{$this->commit}",
            "/api/repositories/$repo/issues",
            "/api/issues/{$this->issue}",
            "/api/issues/{$this->issue}/comments",
            "/api/repositories/$repo/pull-requests",
            "/api/pull-requests/{$this->pr}",
            "/api/pull-requests/{$this->pr}/files",
            "/api/pull-requests/{$this->pr}/comments",
        ];
    }

    public function test_private_repository_is_hidden_with_404_for_guests_and_non_members(): void
    {
        $this->asGuest();
        foreach ($this->readEndpoints() as $url) {
            $this->getJson($url)->assertNotFound();
        }
        $this->getJson("/api/projects/{$this->ctx['project']}/repositories")->assertNotFound();

        $this->actingAsUser(User::factory()->create());
        foreach ($this->readEndpoints() as $url) {
            $this->getJson($url)->assertNotFound();
        }
        $this->getJson("/api/projects/{$this->ctx['project']}")->assertNotFound();
        $this->postJson("/api/repositories/{$this->ctx['repo']}/issues", ['title' => 'x'])->assertNotFound();
        $this->postJson("/api/issues/{$this->issue}/comments", ['body' => 'x'])->assertNotFound();
    }

    public function test_guest_writes_require_authentication(): void
    {
        $this->asGuest();
        ['project' => $project, 'repo' => $repo, 'main' => $main] = $this->ctx;

        $this->postJson("/api/repositories/$repo/issues", ['title' => 'x'])->assertUnauthorized();
        $this->postJson("/api/issues/{$this->issue}/comments", ['body' => 'x'])->assertUnauthorized();
        $this->postJson("/api/branches/$main/commits", ['message' => 'x', 'changes' => []])->assertUnauthorized();
        $this->postJson("/api/pull-requests/{$this->pr}/merge")->assertUnauthorized();
        $this->putJson("/api/repositories/$repo", ['visibility' => 'public'])->assertUnauthorized();
        $this->getJson("/api/projects/$project")->assertUnauthorized();
        $this->getJson('/api/me/notifications')->assertUnauthorized();
    }

    public function test_public_repository_is_readable_without_login(): void
    {
        $this->makePublic($this->ctx['repo']);
        $this->asGuest();

        foreach ($this->readEndpoints() as $url) {
            $this->getJson($url)->assertOk();
        }
        $this->get("/api/branches/{$this->ctx['main']}/file?path=README.md&download=1")->assertOk()->assertDownload('README.md');
        $this->getJson("/api/repositories/{$this->ctx['repo']}")->assertJsonPath('can.push', false);
        $this->getJson("/api/projects/{$this->ctx['project']}/repositories")->assertOk()->assertJsonCount(1);

        // Escribir sigue exigiendo login
        $this->postJson("/api/repositories/{$this->ctx['repo']}/issues", ['title' => 'x'])->assertUnauthorized();
    }

    public function test_registered_non_member_can_read_public_repository_but_not_write(): void
    {
        $this->makePublic($this->ctx['repo']);
        $this->actingAsUser(User::factory()->create());

        $this->getJson("/api/repositories/{$this->ctx['repo']}")->assertOk();
        $this->postJson("/api/repositories/{$this->ctx['repo']}/issues", ['title' => 'x'])->assertForbidden();
        $this->postJson("/api/issues/{$this->issue}/comments", ['body' => 'x'])->assertForbidden();
        $this->postJson("/api/branches/{$this->ctx['main']}/commits", [
            'message' => 'x', 'changes' => [['path' => 'a.txt', 'content' => 'a']],
        ])->assertForbidden();
    }

    public function test_bearer_token_identifies_member_on_public_read_routes(): void
    {
        $member = User::factory()->create();
        $this->postJson("/api/projects/{$this->ctx['project']}/members", ['email' => $member->email, 'role' => 'observer'])->assertCreated();
        $token = $member->createToken('test')->plainTextToken;

        $this->asGuest();
        $this->getJson("/api/repositories/{$this->ctx['repo']}")->assertNotFound();

        $this->asGuest();
        $this->withToken($token)->getJson("/api/repositories/{$this->ctx['repo']}")->assertOk();
    }

    public function test_explore_lists_only_public_repositories(): void
    {
        $this->requireVisibilityColumn();

        $this->postJson("/api/projects/{$this->ctx['project']}/repositories", ['name' => 'web-publica', 'visibility' => 'public'])
            ->assertCreated()->assertJsonPath('visibility', 'public');

        $this->asGuest();
        $this->getJson('/api/explore/repositories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'web-publica')
            ->assertJsonPath('data.0.project.name', 'Proyecto DAM');
        $this->getJson('/api/explore/repositories?q=nada')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_only_owner_can_change_visibility(): void
    {
        $this->requireVisibilityColumn();

        $dev = User::factory()->create();
        $this->postJson("/api/projects/{$this->ctx['project']}/members", ['email' => $dev->email, 'role' => 'collaborator'])->assertCreated();

        $this->putJson("/api/repositories/{$this->ctx['repo']}", ['visibility' => 'secreto'])->assertUnprocessable();

        $this->actingAsUser($dev);
        $this->putJson("/api/repositories/{$this->ctx['repo']}", ['visibility' => 'public'])->assertForbidden();

        $this->actingAsUser($this->owner);
        $this->putJson("/api/repositories/{$this->ctx['repo']}", ['visibility' => 'public'])
            ->assertOk()->assertJsonPath('visibility', 'public');

        $this->asGuest();
        $this->getJson("/api/repositories/{$this->ctx['repo']}")->assertOk();
    }
}
