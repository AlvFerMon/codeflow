<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/** Tareas diarias: comando y endpoint para Vercel Cron. */
class CronTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    public function test_endpoint_requires_the_secret(): void
    {
        $this->getJson('/api/cron/daily')->assertUnauthorized(); // sin secreto configurado

        config(['codeflow.cron_secret' => 's3creto']);
        $this->getJson('/api/cron/daily')->assertUnauthorized();
        $this->getJson('/api/cron/daily', ['Authorization' => 'Bearer otro'])->assertUnauthorized();
        $this->getJson('/api/cron/daily', ['Authorization' => 'Bearer s3creto'])->assertOk()->assertJsonPath('ok', true);
    }

    public function test_daily_tasks_clean_old_data(): void
    {
        config(['session.driver' => 'database', 'codeflow.cron_secret' => 'x']);
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $this->deleteJson("/api/projects/{$ctx['project']}")->assertSuccessful();

        ProjectInvitation::forceCreate([
            'project_id' => $this->buildProject($owner, [], 'otro')['project'], 'email' => 'viejo@ejemplo.com', 'role' => 'observer',
            'token_hash' => str_repeat('a', 64), 'invited_by' => $owner->id, 'expires_at' => now()->subDays(40),
        ]);
        DB::table('sessions')->insert(['id' => str_repeat('s', 40), 'user_id' => $owner->id, 'payload' => '', 'last_activity' => now()->subDays(2)->timestamp]);

        $owner->createToken('codeflow-cli viejo')->accessToken->forceFill(['last_used_at' => now()->subDays(200)])->save();
        $owner->createToken('codeflow-cli nuevo');

        $this->travel(31)->days();
        $this->getJson('/api/cron/daily', ['Authorization' => 'Bearer x'])->assertOk()
            ->assertJsonPath('done.trash_purged', 1)
            ->assertJsonPath('done.invitations_deleted', 1)
            ->assertJsonPath('done.sessions_deleted', 1)
            ->assertJsonPath('done.tokens_deleted', 1);

        $this->assertNull(Project::withTrashed()->find($ctx['project']));
        $this->artisan('codeflow:daily')->assertSuccessful();
    }
}
