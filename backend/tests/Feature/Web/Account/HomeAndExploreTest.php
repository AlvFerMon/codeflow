<?php

namespace Tests\Feature\Web\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAndExploreTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    public function test_guest_sees_landing_page(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Colabora en tu código con CodeFlow')
            ->assertSee(route('register'))
            ->assertSee('Explorar repositorios públicos');
    }

    public function test_dashboard_shows_only_my_projects_activity_and_notifications(): void
    {
        $this->setUpTeam();
        $this->makeProject($this->outsider, 'Proyecto secreto');

        $this->as($this->collaborator)->get('/')->assertOk()
            ->assertSee('Mis proyectos')
            ->assertSee('Proyecto DAM')
            ->assertDontSee('Proyecto secreto')
            ->assertSee('Colaborador')
            // Notificación de alta y actividad del proyecto
            ->assertSee('te ha dado acceso al proyecto Proyecto DAM')
            ->assertSee('creó el proyecto', false)
            ->assertSee('Ver todas');
    }

    public function test_dashboard_for_user_without_projects_shows_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertOk()
            ->assertSee('Todavía no participas en ningún proyecto')
            ->assertSee(route('projects.create'));
    }

    public function test_explore_lists_only_public_repositories_and_searches(): void
    {
        $this->setUpTeam();
        $this->makeRepository($this->project, $this->owner, 'web-publica', 'public', 'Web del proyecto');
        $this->makeRepository($this->project, $this->owner, 'docs-abiertos', 'public', 'Documentación');
        $this->makeRepository($this->project, $this->owner, 'api-privada', 'private', 'Secreto');

        $this->get('/explore')->assertOk()
            ->assertSee('web-publica')->assertSee('docs-abiertos')->assertDontSee('api-privada')
            ->assertSee('Proyecto DAM');

        $this->get('/explore?q=Documentaci')->assertOk()
            ->assertSee('docs-abiertos')->assertDontSee('web-publica');

        $this->get('/explore?q=nada-que-coincida')->assertOk()->assertSee('No hay repositorios públicos que coincidan');
    }
}
