<?php

namespace Tests\Feature\Web\Account;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPagesTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTeam();
    }

    public function test_user_can_create_project_and_becomes_owner(): void
    {
        $this->as($this->outsider)->get('/projects/create')->assertOk()->assertSee('Nuevo proyecto');

        $response = $this->post('/projects', ['name' => 'Mi TFG', 'description' => 'Hola']);
        $project = Project::where('name', 'Mi TFG')->firstOrFail();

        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame('owner', $this->outsider->fresh()->roleIn($project));

        $this->from('/projects/create')->post('/projects', ['name' => ''])->assertSessionHasErrors('name');
    }

    public function test_index_lists_my_projects_and_admin_sees_all(): void
    {
        $this->makeProject($this->outsider, 'Proyecto ajeno');

        $this->as($this->observer)->get('/projects')->assertOk()
            ->assertSee('Proyecto DAM')->assertDontSee('Proyecto ajeno')->assertSee('Observador');

        $this->as($this->admin)->get('/projects')->assertOk()
            ->assertSee('Proyecto DAM')->assertSee('Proyecto ajeno')->assertSee('todos los proyectos');
    }

    public function test_show_permissions_by_role(): void
    {
        $this->makeRepository($this->project, $this->owner, 'api-privada');
        $this->makeRepository($this->project, $this->owner, 'web-publica', 'public');
        $url = route('projects.show', $this->project);

        $this->as($this->owner)->get($url)->assertOk()
            ->assertSee('api-privada')->assertSee('Privado')->assertSee('Público')
            ->assertSee('<strong>importante</strong>', false)
            ->assertSee('Nuevo repositorio')->assertSee(route('projects.edit', $this->project));

        foreach ([$this->collaborator, $this->observer] as $member) {
            $this->as($member)->get($url)->assertOk()
                ->assertSee('api-privada')
                ->assertDontSee('Nuevo repositorio')
                ->assertDontSee(route('projects.edit', $this->project));
        }

        $this->as($this->outsider)->get($url)->assertNotFound();
        $this->as($this->admin)->get($url)->assertOk()->assertSee('Nuevo repositorio');
    }

    public function test_description_markdown_is_escaped(): void
    {
        $this->project->update(['description' => "<script>alert(1)</script>\n\n[x](javascript:alert(1))"]);

        $this->as($this->owner)->get(route('projects.show', $this->project))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('href="javascript:', false);
    }

    public function test_only_owner_and_admin_can_edit(): void
    {
        $edit = route('projects.edit', $this->project);

        $this->as($this->owner)->get($edit)->assertOk()->assertSee('Eliminar proyecto');
        $this->as($this->admin)->get($edit)->assertOk();
        $this->as($this->collaborator)->get($edit)->assertForbidden();
        $this->as($this->observer)->get($edit)->assertForbidden();
        $this->as($this->outsider)->get($edit)->assertNotFound();

        $this->as($this->collaborator)->put(route('projects.update', $this->project), ['name' => 'Hackeado'])->assertForbidden();

        $this->as($this->owner)->put(route('projects.update', $this->project), ['name' => 'Proyecto renombrado', 'description' => 'Nueva'])
            ->assertRedirect(route('projects.show', $this->project))->assertSessionHas('status');
        $this->assertSame('Proyecto renombrado', $this->project->fresh()->name);
    }

    public function test_only_owner_or_admin_can_delete(): void
    {
        $destroy = route('projects.destroy', $this->project);

        $this->as($this->collaborator)->delete($destroy)->assertForbidden();
        $this->as($this->observer)->delete($destroy)->assertForbidden();
        $this->as($this->outsider)->delete($destroy)->assertNotFound();
        $this->assertNotNull($this->project->fresh());

        $this->as($this->owner)->delete($destroy)->assertRedirect(route('projects.index'))->assertSessionHas('status');
        $this->assertTrue($this->project->fresh()->trashed()); // a la papelera

        $other = $this->makeProject($this->outsider, 'Otro');
        $this->as($this->admin)->delete(route('projects.destroy', $other))->assertRedirect(route('projects.index'));
        $this->assertTrue($other->fresh()->trashed());
    }

    public function test_activity_page_shows_human_sentences_for_members_only(): void
    {
        $repo = $this->makeRepository($this->project, $this->owner, 'codeflow-api');
        $url = route('projects.activity', $this->project);

        $this->as($this->observer)->get($url)->assertOk()
            ->assertSee('creó el proyecto')
            ->assertSee('dio acceso a')
            ->assertSee('creó el repositorio')
            ->assertSee(route('repositories.show', $repo))
            ->assertSee('Laura Propietaria');

        $this->as($this->outsider)->get($url)->assertNotFound();
    }
}
