<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

class ObserverTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    private User $owner;

    private User $dev;

    private User $observer;

    private array $ctx;

    private int $issue;

    private int $feature;

    private int $pr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->dev = User::factory()->create();
        $this->observer = User::factory()->create();

        $this->ctx = $this->buildProject($this->owner, [[$this->dev, 'collaborator'], [$this->observer, 'observer']]);

        $this->actingAsUser($this->dev);
        $this->issue = $this->postJson("/api/repositories/{$this->ctx['repo']}/issues", ['title' => 'Bug'])->assertCreated()->json('id');
        [$this->feature, $this->pr] = $this->openPullRequest($this->ctx);

        $this->actingAsUser($this->observer);
    }

    public function test_observer_can_read_private_repository(): void
    {
        ['project' => $project, 'repo' => $repo, 'main' => $main] = $this->ctx;

        $this->getJson("/api/projects/$project")->assertOk()->assertJsonPath('role', 'observer');
        $this->getJson("/api/projects/$project/activity")->assertOk();
        $this->getJson("/api/projects/$project/members")->assertOk();
        $this->getJson("/api/repositories/$repo")->assertOk()->assertJsonPath('can.push', false);
        $this->getJson("/api/repositories/$repo/branches")->assertOk();
        $this->getJson("/api/branches/$main/files")->assertOk();
        $this->getJson("/api/branches/$main/file?path=README.md")->assertOk();
        $this->getJson("/api/repositories/$repo/commits")->assertOk();
        $this->getJson("/api/repositories/$repo/issues")->assertOk();
        $this->getJson("/api/issues/{$this->issue}")->assertOk();
        $this->getJson("/api/pull-requests/{$this->pr}")->assertOk();
        $this->getJson("/api/pull-requests/{$this->pr}/files")->assertOk();
    }

    public function test_observer_can_post_general_comments_on_issues_and_pull_requests(): void
    {
        $this->postJson("/api/issues/{$this->issue}/comments", ['body' => '¿Esto afecta al login?'])->assertCreated();
        $this->postJson("/api/pull-requests/{$this->pr}/comments", ['body' => 'Buena pinta'])->assertCreated();
    }

    public function test_observer_cannot_comment_on_code(): void
    {
        $this->postJson("/api/pull-requests/{$this->pr}/comments", ['body' => 'Aquí falla', 'path' => 'src/a.php', 'line' => 3])
            ->assertForbidden();
        $this->postJson("/api/pull-requests/{$this->pr}/comments", ['body' => 'Aquí falla', 'path' => 'src/a.php'])
            ->assertForbidden();

        // Un colaborador sí puede
        $this->actingAsUser($this->owner);
        $this->postJson("/api/pull-requests/{$this->pr}/comments", ['body' => 'Revisar', 'path' => 'src/a.php', 'line' => 3])
            ->assertCreated()->assertJsonPath('line', 3);
    }

    public function test_observer_cannot_write_anything_else(): void
    {
        ['project' => $project, 'repo' => $repo, 'main' => $main] = $this->ctx;

        $this->postJson("/api/repositories/$repo/issues", ['title' => 'Nueva'])->assertForbidden();
        $this->putJson("/api/issues/{$this->issue}", ['status' => 'closed'])->assertForbidden();
        $this->deleteJson("/api/issues/{$this->issue}")->assertForbidden();
        $this->postJson("/api/repositories/$repo/branches", ['name' => 'obs'])->assertForbidden();
        $this->deleteJson("/api/branches/{$this->feature}")->assertForbidden();
        $this->postJson("/api/branches/$main/commits", ['message' => 'x', 'changes' => [['path' => 'a.txt', 'content' => 'a']]])
            ->assertForbidden();
        $this->postJson("/api/repositories/$repo/pull-requests", [
            'title' => 'PR', 'source_branch_id' => $this->feature, 'target_branch_id' => $main,
        ])->assertForbidden();
        $this->putJson("/api/pull-requests/{$this->pr}", ['title' => 'Otro'])->assertForbidden();
        $this->postJson("/api/pull-requests/{$this->pr}/reviews", ['state' => 'approved'])->assertForbidden();
        $this->postJson("/api/pull-requests/{$this->pr}/reviews", ['state' => 'commented', 'body' => 'x'])->assertForbidden();
        $this->postJson("/api/pull-requests/{$this->pr}/merge")->assertForbidden();
        $this->postJson("/api/pull-requests/{$this->pr}/close")->assertForbidden();
        $this->putJson("/api/repositories/$repo", ['description' => 'x'])->assertForbidden();
        $this->deleteJson("/api/repositories/$repo")->assertForbidden();
        $this->postJson("/api/projects/$project/repositories", ['name' => 'otro'])->assertForbidden();
        $this->putJson("/api/projects/$project", ['name' => 'x'])->assertForbidden();
    }

    public function test_only_the_owner_can_manage_members(): void
    {
        $project = $this->ctx['project'];
        $newcomer = User::factory()->create();

        $this->postJson("/api/projects/$project/members", ['email' => $newcomer->email, 'role' => 'observer'])->assertForbidden();
        $this->putJson("/api/projects/$project/members/{$this->dev->id}", ['role' => 'observer'])->assertForbidden();

        $this->actingAsUser($this->dev);
        $this->postJson("/api/projects/$project/members", ['email' => $newcomer->email, 'role' => 'observer'])->assertForbidden();
        $this->deleteJson("/api/projects/$project/members/{$this->observer->id}")->assertForbidden();

        $this->actingAsUser($this->owner);
        $this->postJson("/api/projects/$project/members", ['email' => $newcomer->email, 'role' => 'observer'])
            ->assertCreated()->assertJsonPath('role', 'observer');
        $this->postJson("/api/projects/$project/members", ['email' => $newcomer->email, 'role' => 'owner'])->assertUnprocessable();
        $this->putJson("/api/projects/$project/members/{$this->owner->id}", ['role' => 'observer'])->assertUnprocessable();
        $this->deleteJson("/api/projects/$project/members/{$this->owner->id}")->assertUnprocessable();
    }

    public function test_promoted_observer_gains_collaborator_permissions(): void
    {
        $this->actingAsUser($this->owner);
        $this->putJson("/api/projects/{$this->ctx['project']}/members/{$this->observer->id}", ['role' => 'collaborator'])
            ->assertOk()->assertJsonPath('role', 'collaborator');

        $this->actingAsUser($this->observer);
        $this->postJson("/api/repositories/{$this->ctx['repo']}/issues", ['title' => 'Ahora sí'])->assertCreated();
        $this->postJson("/api/pull-requests/{$this->pr}/reviews", ['state' => 'approved'])->assertCreated();
    }

    public function test_admin_can_do_everything(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAsUser($admin);
        $this->getJson("/api/repositories/{$this->ctx['repo']}")->assertOk();
        $this->postJson("/api/projects/{$this->ctx['project']}/members", ['email' => User::factory()->create()->email, 'role' => 'collaborator'])
            ->assertCreated();
        $this->postJson("/api/pull-requests/{$this->pr}/merge")->assertOk()->assertJsonPath('status', 'merged');
    }
}
