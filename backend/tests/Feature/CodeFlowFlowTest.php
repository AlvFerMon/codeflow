<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CodeFlowFlowTest extends TestCase
{
    use RefreshDatabase;

    private function setupProject(User $owner, User $dev): array
    {
        Sanctum::actingAs($owner);

        $project = $this->postJson('/api/projects', ['name' => 'DAM'])->assertCreated()->json('id');
        $this->postJson("/api/projects/$project/members", ['email' => $dev->email, 'role' => 'collaborator'])->assertCreated();
        $repo = $this->postJson("/api/projects/$project/repositories", ['name' => 'app'])->assertCreated()->json('id');

        $branches = $this->getJson("/api/repositories/$repo/branches")->assertOk()->json();
        $main = collect($branches)->firstWhere('name', 'main')['id'];

        return [$project, $repo, $main];
    }

    public function test_register_login_and_me(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Carlos', 'email' => 'carlos@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertCreated()->assertJsonStructure(['user', 'token']);

        $this->postJson('/api/auth/login', ['email' => 'carlos@example.com', 'password' => 'wrong'])
            ->assertStatus(422);

        $token = $this->postJson('/api/auth/login', ['email' => 'carlos@example.com', 'password' => 'secret123'])
            ->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('email', 'carlos@example.com');
    }

    public function test_branch_pull_request_review_and_merge(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [$project, $repo, $main] = $this->setupProject($owner, $dev);

        $feature = $this->postJson("/api/repositories/$repo/branches", ['name' => 'feature/login', 'from_branch_id' => $main])
            ->assertCreated()->json('id');

        $this->postJson("/api/branches/$feature/commits", [
            'message' => 'Implementado login',
            'changes' => [['path' => 'src/Login.php', 'content' => '<?php // login']],
        ])->assertCreated();

        $pr = $this->postJson("/api/repositories/$repo/pull-requests", [
            'title' => 'Login', 'source_branch_id' => $feature, 'target_branch_id' => $main,
        ])->assertCreated()->json('id');

        // El autor no puede fusionar sin aprobación siendo colaborador; aquí actúa el otro usuario
        Sanctum::actingAs($dev);
        $this->postJson("/api/pull-requests/$pr/reviews", ['state' => 'approved'])->assertCreated();
        $this->postJson("/api/pull-requests/$pr/merge")->assertOk()->assertJsonPath('status', 'merged');

        $this->getJson("/api/branches/$main/file?path=src/Login.php")
            ->assertOk()->assertJsonPath('content', '<?php // login');
    }

    public function test_observer_cannot_commit(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        Sanctum::actingAs($owner);

        $project = $this->postJson('/api/projects', ['name' => 'P'])->json('id');
        $this->postJson("/api/projects/$project/members", ['email' => $viewer->email, 'role' => 'observer'])->assertCreated();
        $repo = $this->postJson("/api/projects/$project/repositories", ['name' => 'r'])->json('id');
        $main = collect($this->getJson("/api/repositories/$repo/branches")->json())->firstWhere('name', 'main')['id'];

        Sanctum::actingAs($viewer);
        $this->getJson("/api/branches/$main/files")->assertOk();
        $this->postJson("/api/branches/$main/commits", [
            'message' => 'x', 'changes' => [['path' => 'a.txt', 'content' => 'a']],
        ])->assertForbidden();
    }

    public function test_merge_conflict_returns_409(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [$project, $repo, $main] = $this->setupProject($owner, $dev);

        $feature = $this->postJson("/api/repositories/$repo/branches", ['name' => 'feature/x', 'from_branch_id' => $main])->json('id');

        $this->postJson("/api/branches/$feature/commits", [
            'message' => 'cambio en feature', 'changes' => [['path' => 'README.md', 'content' => "# feature\n"]],
        ])->assertCreated();
        $this->postJson("/api/branches/$main/commits", [
            'message' => 'cambio en main', 'changes' => [['path' => 'README.md', 'content' => "# main\n"]],
        ])->assertCreated();

        $pr = $this->postJson("/api/repositories/$repo/pull-requests", [
            'title' => 'X', 'source_branch_id' => $feature, 'target_branch_id' => $main,
        ])->assertCreated()->json('id');

        $this->getJson("/api/pull-requests/$pr")->assertJsonPath('mergeable', false);

        // El propietario puede fusionar sin aprobación, pero hay conflicto
        $this->postJson("/api/pull-requests/$pr/merge")->assertStatus(409)->assertJsonPath('conflicts.0', 'README.md');
    }

    public function test_issue_numbers_and_status_change(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [$project, $repo] = $this->setupProject($owner, $dev);

        $first = $this->postJson("/api/repositories/$repo/issues", ['title' => 'Bug login', 'priority' => 'high'])
            ->assertCreated()->assertJsonPath('number', 1)->json('id');
        $this->postJson("/api/repositories/$repo/issues", ['title' => 'Otro'])->assertJsonPath('number', 2);

        $this->putJson("/api/issues/$first", ['status' => 'in_progress', 'assignee_id' => $dev->id])
            ->assertOk()->assertJsonPath('status', 'in_progress');

        $this->getJson("/api/projects/$project/activity")->assertOk();
    }

    public function test_close_reopen_and_branch_deletion_rules(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        [$project, $repo, $main] = $this->setupProject($owner, $dev);

        Sanctum::actingAs($dev);
        $feature = $this->postJson("/api/repositories/$repo/branches", ['name' => 'feature/y', 'from_branch_id' => $main])->json('id');
        $this->postJson("/api/branches/$feature/commits", [
            'message' => 'y', 'changes' => [['path' => 'y.txt', 'content' => 'y']],
        ])->assertCreated();
        $pr = $this->postJson("/api/repositories/$repo/pull-requests", [
            'title' => 'Y', 'source_branch_id' => $feature, 'target_branch_id' => $main,
        ])->assertCreated()->json('id');

        // El autor no puede revisar su propio PR ni un colaborador fusionarlo sin aprobación
        $this->postJson("/api/pull-requests/$pr/reviews", ['state' => 'approved'])->assertForbidden();
        $this->postJson("/api/pull-requests/$pr/merge")->assertForbidden();

        // Ni la rama por defecto ni una con PR abierto se pueden borrar
        $this->deleteJson("/api/branches/$main")->assertUnprocessable();
        $this->deleteJson("/api/branches/$feature")->assertUnprocessable();

        $this->postJson("/api/pull-requests/$pr/close")->assertOk()->assertJsonPath('status', 'closed');
        $this->postJson("/api/pull-requests/$pr/reopen")->assertOk()->assertJsonPath('status', 'open');
        $this->postJson("/api/pull-requests/$pr/close")->assertOk();

        $this->deleteJson("/api/branches/$feature")->assertNoContent();
        $this->postJson("/api/pull-requests/$pr/reopen")->assertUnprocessable();
    }
}
