<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Repository;
use App\Models\User;
use App\Services\TrashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/** Papelera de proyectos y repositorios (30 días). */
class TrashTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    public function test_a_deleted_project_goes_to_the_trash_with_its_repositories_and_can_be_restored(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $issue = $this->postJson("/api/repositories/{$ctx['repo']}/issues", ['title' => 'Bug'])->assertCreated()->json('id');

        $this->deleteJson("/api/projects/{$ctx['project']}")->assertSuccessful();

        // Desaparece de todas partes
        $this->getJson("/api/projects/{$ctx['project']}")->assertNotFound();
        $this->getJson("/api/repositories/{$ctx['repo']}")->assertNotFound();
        $this->getJson("/api/branches/{$ctx['main']}/files")->assertNotFound();
        $this->getJson("/api/issues/$issue")->assertNotFound();
        $this->assertSame([], $this->getJson('/api/projects')->json('data'));

        // …y está en la papelera
        $this->getJson('/api/trash')->assertOk()
            ->assertJsonPath('days', 30)
            ->assertJsonPath('projects.0.id', $ctx['project'])
            ->assertJsonPath('projects.0.repositories', 1)
            ->assertJsonCount(0, 'repositories');

        $this->postJson("/api/trash/projects/{$ctx['project']}/restore")->assertOk();
        $this->getJson("/api/repositories/{$ctx['repo']}")->assertOk();
        $this->getJson("/api/issues/$issue")->assertOk();
    }

    public function test_repositories_deleted_before_the_project_stay_in_the_trash_when_the_project_is_restored(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $other = $this->postJson("/api/projects/{$ctx['project']}/repositories", ['name' => 'otro'])->assertCreated()->json('id');

        $this->deleteJson("/api/repositories/$other")->assertSuccessful();
        $this->travel(1)->minutes();
        $this->deleteJson("/api/projects/{$ctx['project']}")->assertSuccessful();
        $this->postJson("/api/trash/projects/{$ctx['project']}/restore")->assertOk();

        $this->getJson("/api/repositories/{$ctx['repo']}")->assertOk();
        $this->getJson("/api/repositories/$other")->assertNotFound();
        $this->getJson('/api/trash')->assertJsonPath('repositories.0.id', $other);

        // Mientras esté en la papelera no se puede crear otro con el mismo nombre
        $this->postJson("/api/projects/{$ctx['project']}/repositories", ['name' => 'otro'])->assertStatus(422);
        $this->postJson("/api/trash/repositories/$other/restore")->assertOk();
        $this->getJson("/api/repositories/$other")->assertOk();
    }

    public function test_permanent_delete_and_automatic_purge_after_30_days(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $second = $this->buildProject($owner, [], 'segundo');

        // Hay que pasar por la papelera antes de borrar del todo
        $this->deleteJson("/api/trash/projects/{$ctx['project']}")->assertNotFound();

        $this->deleteJson("/api/projects/{$ctx['project']}")->assertSuccessful();
        $this->deleteJson("/api/trash/projects/{$ctx['project']}")->assertOk();
        $this->assertNull(Project::withTrashed()->find($ctx['project']));
        $this->assertNull(Repository::withTrashed()->find($ctx['repo']));

        $this->deleteJson("/api/repositories/{$second['repo']}")->assertSuccessful();
        $this->travel(TrashService::DAYS - 1)->days();
        $this->assertSame(0, app(TrashService::class)->purgeExpired());
        $this->travel(2)->days();
        $this->artisan('codeflow:empty-trash')->assertSuccessful();
        $this->assertNull(Repository::withTrashed()->find($second['repo']));
        $this->assertNotNull(Project::find($second['project']));
    }

    public function test_only_the_owner_can_restore_or_purge(): void
    {
        $owner = User::factory()->create();
        $mate = User::factory()->create();
        $ctx = $this->buildProject($owner, [[$mate, 'collaborator']]);
        $this->deleteJson("/api/repositories/{$ctx['repo']}")->assertSuccessful();

        $this->actingAsUser($mate);
        $this->getJson('/api/trash')->assertOk()->assertJsonCount(0, 'repositories');
        $this->postJson("/api/trash/repositories/{$ctx['repo']}/restore")->assertForbidden();
        $this->deleteJson("/api/trash/repositories/{$ctx['repo']}")->assertForbidden();
    }

    public function test_web_trash_page(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        $project = Project::find($ctx['project']);

        $this->actingAs($owner)->delete(route('projects.destroy', $project))->assertRedirect();
        $this->actingAs($owner)->get(route('trash.index'))->assertOk()->assertSee('Proyecto DAM')->assertSee('Restaurar');
        $this->actingAs($owner)->get('/')->assertOk()->assertDontSee('Proyecto DAM');

        $this->actingAs($owner)->post(route('trash.projects.restore', $project->id))->assertRedirect(route('projects.show', $project));
        $this->actingAs($owner)->get(route('trash.index'))->assertOk()->assertSee('La papelera está vacía');
    }
}
