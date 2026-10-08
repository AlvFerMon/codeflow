<?php

namespace Tests\Feature\Web\Code;

use App\Models\Commit;
use App\Models\Repository;
use App\Services\RepositoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryAdminTest extends TestCase
{
    use BuildsCodeFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFixtures();
    }

    public function test_owner_creates_repository_with_readme(): void
    {
        $this->signIn($this->owner);

        $this->get(route('repositories.create', $this->project))->assertOk()->assertSee('Inicializar con un README');

        $response = $this->post(route('repositories.store', $this->project), [
            'name' => 'nuevo-repo', 'description' => 'Descripción', 'visibility' => 'public', 'init_readme' => '1',
        ]);
        $repo = Repository::where('name', 'nuevo-repo')->firstOrFail();
        $response->assertRedirect(route('repositories.show', $repo));

        $this->assertTrue($repo->isPublic());
        $this->assertSame(1, Commit::where('repository_id', $repo->id)->count());
        $this->get(route('repositories.show', $repo))->assertOk()->assertSee('README.md');
    }

    public function test_repository_without_readme_shows_empty_state(): void
    {
        $this->signIn($this->owner);

        $this->post(route('repositories.store', $this->project), ['name' => 'vacio', 'visibility' => 'private'])->assertRedirect();
        $repo = Repository::where('name', 'vacio')->firstOrFail();

        $this->assertFalse($repo->isPublic());
        $this->assertSame(0, Commit::where('repository_id', $repo->id)->count());
        $this->get(route('repositories.show', $repo))->assertOk()->assertSee('Este repositorio está vacío')->assertSee('Subir archivos');
        $this->get(route('commits.index', $repo))->assertOk()->assertSee('No hay commits');
    }

    public function test_repository_name_is_validated(): void
    {
        $this->signIn($this->owner);

        $this->post(route('repositories.store', $this->project), ['name' => 'api', 'visibility' => 'private'])->assertSessionHasErrors('name');
        $this->post(route('repositories.store', $this->project), ['name' => 'con espacios', 'visibility' => 'private'])->assertSessionHasErrors('name');
        $this->post(route('repositories.store', $this->project), ['name' => 'ok', 'visibility' => 'secreto'])->assertSessionHasErrors('visibility');
    }

    public function test_only_owner_can_create_configure_or_delete(): void
    {
        foreach ([$this->collaborator, $this->observer] as $user) {
            $this->signIn($user);
            $this->get(route('repositories.create', $this->project))->assertForbidden();
            $this->post(route('repositories.store', $this->project), ['name' => 'x', 'visibility' => 'private'])->assertForbidden();
            $this->get(route('repositories.settings', $this->private))->assertForbidden();
            $this->put(route('repositories.update', $this->private), ['name' => 'x', 'visibility' => 'public', 'default_branch' => 'main'])->assertForbidden();
            $this->delete(route('repositories.destroy', $this->private), ['confirm_name' => 'api'])->assertForbidden();
            $this->get(route('repositories.show', $this->private))->assertOk()->assertDontSee('Configuración');
        }

        $this->signIn($this->outsider);
        $this->get(route('repositories.create', $this->project))->assertNotFound();
        $this->get(route('repositories.settings', $this->private))->assertNotFound();
        $this->get(route('repositories.settings', $this->public))->assertForbidden();

        $this->assertNotNull(Repository::find($this->private->id));
    }

    public function test_owner_updates_settings_and_default_branch(): void
    {
        app(RepositoryService::class)->createBranch($this->private, $this->owner, 'develop', null);
        $this->signIn($this->owner);

        $this->get(route('repositories.settings', $this->private))->assertOk()->assertSee('Zona de peligro')->assertSee('develop');

        $this->put(route('repositories.update', $this->private), [
            'name' => 'api-v2', 'description' => 'Nueva', 'visibility' => 'public', 'default_branch' => 'develop',
        ])->assertRedirect(route('repositories.settings', $this->private))->assertSessionHas('status');

        $repo = $this->private->fresh();
        $this->assertSame('api-v2', $repo->name);
        $this->assertSame('Nueva', $repo->description);
        $this->assertTrue($repo->isPublic());
        $this->assertSame('develop', $repo->default_branch);

        $this->put(route('repositories.update', $this->private), [
            'name' => 'api-v2', 'visibility' => 'public', 'default_branch' => 'no-existe',
        ])->assertSessionHasErrors('default_branch');
        $this->put(route('repositories.update', $this->private), [
            'name' => 'web', 'visibility' => 'public', 'default_branch' => 'main',
        ])->assertSessionHasErrors('name');
    }

    public function test_owner_deletes_repository_typing_its_name(): void
    {
        $this->signIn($this->owner);

        $this->delete(route('repositories.destroy', $this->private), ['confirm_name' => 'otro'])->assertSessionHasErrors('confirm_name');
        $this->assertNotNull(Repository::find($this->private->id));

        $this->delete(route('repositories.destroy', $this->private), ['confirm_name' => 'api'])
            ->assertRedirect(route('projects.show', $this->project))->assertSessionHas('status');
        $this->assertNull(Repository::find($this->private->id));
    }
}
