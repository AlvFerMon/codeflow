<?php

namespace Tests\Feature\Web\Account;

use App\Models\PullRequest;
use App\Models\User;
use App\Services\IssueService;
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
            ->assertSee('Explorar repositorios públicos')
            // Layout del diseño unificado: selector de tema y buscador global
            ->assertSee('data-theme-set="dark"', false)
            ->assertSee('data-search-shortcut', false);
    }

    public function test_dashboard_shows_only_my_projects_and_header_accesses(): void
    {
        $this->setUpTeam();
        $this->makeRepository($this->project, $this->owner, 'api', 'private');
        $this->makeRepository($this->makeProject($this->outsider, 'Proyecto secreto'), $this->outsider, 'repo-secreto');

        // El panel es un resumen (métricas y repositorios); actividad y notificaciones están en la cabecera
        $this->as($this->collaborator)->get('/')->assertOk()
            ->assertSee('Mis proyectos')
            ->assertSeeText('Proyecto DAM / api')
            ->assertDontSee('Proyecto secreto')
            ->assertDontSee('repo-secreto')
            ->assertSee(route('activity.index'), false)
            ->assertSee(route('notifications.index'), false);
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

    public function test_dashboard_shows_real_metrics_and_recent_repositories(): void
    {
        $this->setUpTeam();
        $repo = $this->makeRepository($this->project, $this->owner, 'api-backend', 'private', 'API del proyecto');
        $this->makeRepository($this->project, $this->owner, 'web-publica', 'public');
        $ajeno = $this->makeRepository($this->makeProject($this->outsider, 'Ajeno'), $this->outsider, 'repo-ajeno');

        $issues = app(IssueService::class);
        $issues->create($repo, $this->collaborator, ['title' => 'Fallo grave', 'priority' => 'critical']);
        $issues->create($repo, $this->collaborator, ['title' => 'Detalle menor', 'priority' => 'low']);
        $issues->create($ajeno, $this->outsider, ['title' => 'No cuenta', 'priority' => 'high']);

        $pull = PullRequest::forceCreate([
            'repository_id' => $repo->id, 'number' => 1, 'title' => 'Login de usuarios', 'author_id' => $this->collaborator->id,
            'source_branch_name' => 'feature/login', 'target_branch_name' => 'main', 'status' => 'in_review',
        ]);
        $pull->reviewers()->attach($this->owner->id);
        PullRequest::forceCreate([
            'repository_id' => $repo->id, 'number' => 2, 'title' => 'PR ya fusionada', 'author_id' => $this->collaborator->id,
            'source_branch_name' => 'feature/x', 'target_branch_name' => 'main', 'status' => 'merged', 'merged_at' => now()->subDay(),
        ]);
        // Fusionada hace un mes y comentada hoy: no es «de esta semana»
        PullRequest::forceCreate([
            'repository_id' => $repo->id, 'number' => 3, 'title' => 'PR antigua comentada', 'author_id' => $this->collaborator->id,
            'source_branch_name' => 'feature/y', 'target_branch_name' => 'main', 'status' => 'merged', 'merged_at' => now()->subMonth(),
        ]);

        $html = $this->as($this->owner)->get('/')->assertOk()
            ->assertSee('Te toca')
            ->assertSeeText('Proyecto DAM / api-backend')
            ->assertSeeText('Proyecto DAM / web-publica')
            ->assertDontSee('repo-ajeno')
            // «Te toca»: la revisión que me piden, la issue urgente y la PR fusionada esta semana
            ->assertSee('pide tu revisión', false)
            ->assertSee('Prioridad crítica', false)
            ->assertSee('PR ya fusionada', false)
            ->assertDontSee('PR antigua comentada', false)
            ->getContent();

        // Resumen: 2 repositorios, 1 PR abierta, 2 issues abiertas (las del proyecto ajeno no cuentan)
        $text = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('2 repositorios, 1 pull request abierta y 2 issues abiertas', $text);
    }

    public function test_guests_find_public_code_from_landing_and_explore(): void
    {
        $this->setUpTeam();
        $this->makeRepository($this->project, $this->owner, 'web-publica', 'public', 'Web del proyecto');
        $this->makeRepository($this->project, $this->owner, 'api-privada', 'private');
        $other = $this->makeProject($this->outsider, 'Biblioteca abierta');
        $this->makeRepository($other, $this->outsider, 'utilidades', 'public', 'Funciones comunes');

        // Portada: repositorios públicos recientes y buscador
        $this->get('/')->assertOk()
            ->assertSee('Repositorios públicos recientes')
            ->assertSee('web-publica')->assertSee('utilidades')->assertDontSee('api-privada')
            ->assertSee('Ver los 2', false);

        // Explorar: búsqueda también por nombre de proyecto y orden por nombre
        $this->get('/explore?q=Biblioteca')->assertOk()->assertSee('utilidades')->assertDontSee('web-publica');
        $this->get('/explore?orden=nombre')->assertOk()->assertSeeInOrder(['utilidades', 'web-publica'])
            ->assertSee('0 issues abiertas');
        $this->get('/explore?orden=inventado')->assertSessionHasErrors('orden');

        // Repo público visto por un invitado: aviso con acceso a login y registro
        $repo = \App\Models\Repository::where('name', 'web-publica')->first();
        $this->get(route('repositories.show', $repo))->assertOk()
            ->assertSee('Estás viendo un repositorio público.')
            ->assertSee(route('register'));
    }
}
