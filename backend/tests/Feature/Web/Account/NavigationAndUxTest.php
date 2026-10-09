<?php

namespace Tests\Feature\Web\Account;

use App\Models\PullRequest;
use App\Services\IssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase 2 de la auditoría: errores en español, navegación por proyecto, ayudas y confirmaciones. */
class NavigationAndUxTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    public function test_error_pages_are_in_spanish(): void
    {
        $this->get('/repositories/999999')->assertNotFound()
            ->assertSee('No encontramos esta página')->assertSee('Ir al inicio')->assertDontSee('Not Found')->assertDontSee('No query results');

        $this->setUpTeam();
        $this->as($this->observer)->get(route('projects.edit', $this->project))->assertForbidden()
            ->assertSee('No tienes acceso')->assertDontSee('Forbidden');
    }

    public function test_project_tabs_aggregate_issues_and_pulls_of_all_repositories(): void
    {
        $this->setUpTeam();
        $api = $this->makeRepository($this->project, $this->owner, 'api');
        $web = $this->makeRepository($this->project, $this->owner, 'web', 'public');
        $issues = app(IssueService::class);
        $issues->create($api, $this->collaborator, ['title' => 'Fallo en la API']);
        $issues->create($web, $this->collaborator, ['title' => 'Botón roto en la web']);
        $closed = $issues->create($web, $this->collaborator, ['title' => 'Ya resuelta']);
        $issues->update($closed, $this->owner, ['status' => 'closed']);
        PullRequest::forceCreate(['repository_id' => $web->id, 'number' => 1, 'title' => 'Nueva portada', 'author_id' => $this->collaborator->id,
            'source_branch_name' => 'feature/portada', 'target_branch_name' => 'main', 'status' => 'open']);

        $this->as($this->observer);
        $this->get(route('projects.show', $this->project))->assertOk()
            ->assertSee('Resumen')->assertSee(route('projects.issues', $this->project))->assertSee(route('projects.pulls', $this->project));

        $this->get(route('projects.issues', $this->project))->assertOk()
            ->assertSee('Fallo en la API')->assertSee('Botón roto en la web')->assertDontSee('Ya resuelta')
            ->assertSee(route('issues.show', [$api, 1]), false);
        $this->get(route('projects.issues', [$this->project, 'state' => 'closed']))->assertOk()->assertSee('Ya resuelta');
        $this->get(route('projects.issues', [$this->project, 'repo' => $api->id]))->assertOk()
            ->assertSee('Fallo en la API')->assertDontSee('Botón roto en la web');
        $this->get(route('projects.issues', [$this->project, 'q' => 'Botón']))->assertOk()->assertDontSee('Fallo en la API');
        $this->get(route('projects.pulls', $this->project))->assertOk()->assertSee('Nueva portada')->assertSee('feature/portada');

        // Quien no es miembro no ve el proyecto
        $this->as($this->outsider)->get(route('projects.issues', $this->project))->assertNotFound();
    }

    public function test_global_activity_lists_only_my_projects(): void
    {
        $this->setUpTeam();
        $this->makeProject($this->outsider, 'Proyecto ajeno');

        $this->as($this->collaborator)->get(route('activity.index'))->assertOk()
            ->assertSee('Actividad')->assertSee('creó el proyecto', false)->assertDontSee('Proyecto ajeno');
        $this->get(route('activity.index', ['project' => $this->project->id]))->assertOk()->assertSee('Proyecto DAM');
    }

    public function test_destructive_actions_use_the_confirmation_dialog_and_help_texts_exist(): void
    {
        $this->setUpTeam();
        $repo = $this->makeRepository($this->project, $this->owner, 'api');

        $this->as($this->owner)->get(route('projects.edit', $this->project))->assertOk()
            ->assertSee('data-confirm-title="Eliminar el proyecto"', false)
            ->assertSee('id="confirm-dialog"', false)
            ->assertDontSee('return confirm(', false);

        $this->get(route('branches.index', $repo))->assertOk()->assertSee('¿Qué es una rama?');
        $this->get(route('members.index', $this->project))->assertOk()->assertSee('¿Qué puede hacer cada rol?');
    }
}
