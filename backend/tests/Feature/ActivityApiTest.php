<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/** Feed de actividad para la app Android: quién, qué, proyecto, repositorio, rama y hora. */
class ActivityApiTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    public function test_my_activity_lists_events_of_my_projects_only(): void
    {
        $owner = User::factory()->create(['name' => 'Ana']);
        $dev = User::factory()->create(['name' => 'María']);
        $ctx = $this->buildProject($owner, [[$dev, 'collaborator']]);

        $stranger = User::factory()->create();
        $this->buildProject($stranger);

        $this->actingAsUser($dev);
        $this->postJson("/api/branches/{$ctx['main']}/commits", [
            'message' => 'Secreto', 'changes' => [['path' => 'a.txt', 'content' => 'a']],
        ])->assertCreated();

        $this->actingAsUser($owner);
        $data = $this->getJson('/api/me/activity')->assertOk()->json('data');

        $this->assertNotEmpty($data);
        $this->assertSame([$ctx['project']], collect($data)->pluck('project.id')->unique()->values()->all());

        $commit = collect($data)->firstWhere('action', 'commit.created');
        $this->assertSame('María', $commit['actor']['name']);
        $this->assertSame('main', $commit['branch']);
        $this->assertSame('commit', $commit['category']);
        $this->assertSame(7, strlen($commit['commit_sha']));
        $this->assertNotNull($commit['created_at']);
        $this->assertStringNotContainsString('Secreto', json_encode($commit));
        $this->assertStringNotContainsString('<', $commit['summary']);

        // Filtro por proyecto ajeno: se ignora y no filtra datos de otros proyectos
        $this->getJson('/api/me/activity?project_id=999999')->assertOk()
            ->assertJsonMissing(['name' => $stranger->name]);
    }

    public function test_unverified_user_cannot_read_activity(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAsUser($user);

        $this->getJson('/api/me/activity')->assertForbidden()->assertJson(['code' => 'email_not_verified']);
    }
}
