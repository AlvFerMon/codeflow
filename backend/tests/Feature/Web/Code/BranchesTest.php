<?php

namespace Tests\Feature\Web\Code;

use App\Models\Branch;
use App\Services\PullRequestService;
use App\Services\RepositoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchesTest extends TestCase
{
    use BuildsCodeFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFixtures();
    }

    public function test_branches_page_lists_branches_with_details(): void
    {
        $develop = app(RepositoryService::class)->createBranch($this->private, $this->owner, 'develop', null);
        app(RepositoryService::class)->commitChanges($develop, $this->collaborator, 'Solo en develop', [
            ['path' => 'dev.txt', 'content' => "dev\n"],
        ]);

        $this->signIn($this->observer);
        $this->get(route('branches.index', $this->private))->assertOk()
            ->assertSeeInOrder(['main', 'por defecto', 'develop'])
            ->assertSee('Solo en develop')
            ->assertSee('↑1')
            ->assertSee('Creada por Laura')
            ->assertDontSee('Crear rama');
    }

    public function test_collaborator_creates_and_deletes_branches(): void
    {
        $this->signIn($this->collaborator);
        $repo = $this->private;
        $main = $this->branch($repo);

        $this->get(route('branches.index', $repo))->assertOk()->assertSee('Crear rama');

        $this->post(route('branches.store', $repo), ['name' => 'feature/login', 'from_branch_id' => $main->id])
            ->assertRedirect(route('branches.index', $repo))->assertSessionHas('status');
        $feature = $this->branch($repo, 'feature/login');
        $this->assertSame($main->head_commit_id, $feature->head_commit_id);
        $this->assertSame($this->collaborator->id, $feature->created_by);

        // Nombres repetidos o no válidos
        $this->post(route('branches.store', $repo), ['name' => 'feature/login'])->assertSessionHasErrors('name');
        foreach (['mala rama', '/x', 'x/', 'a//b', 'a..b', '.oculta'] as $bad) {
            $this->post(route('branches.store', $repo), ['name' => $bad])->assertSessionHasErrors('name');
        }
        // La rama de origen debe ser de este repositorio
        $other = $this->branch($this->public);
        $this->post(route('branches.store', $repo), ['name' => 'otra', 'from_branch_id' => $other->id])->assertSessionHasErrors('from_branch_id');

        $this->delete(route('branches.destroy', $feature))->assertRedirect(route('branches.index', $repo))->assertSessionHas('status');
        $this->assertNull(Branch::find($feature->id));
    }

    public function test_default_branch_and_branches_with_open_prs_are_protected(): void
    {
        $repo = $this->private;
        $main = $this->branch($repo);
        $feature = app(RepositoryService::class)->createBranch($repo, $this->owner, 'feature/pr', $main->id);
        app(RepositoryService::class)->commitChanges($feature, $this->owner, 'Cambio', [['path' => 'pr.txt', 'content' => 'pr']]);
        app(PullRequestService::class)->open($repo, $this->owner, [
            'title' => 'PR', 'source_branch_id' => $feature->id, 'target_branch_id' => $main->id,
        ]);

        $this->signIn($this->owner);
        $this->get(route('branches.index', $repo))->assertOk()
            ->assertDontSee(route('branches.destroy', $main), false)
            ->assertDontSee(route('branches.destroy', $feature), false)
            ->assertSee('tiene pull requests abiertos');

        $this->from(route('branches.index', $repo))->delete(route('branches.destroy', $main))
            ->assertRedirect(route('branches.index', $repo))->assertSessionHas('error', 'No se puede eliminar la rama por defecto.');
        $this->delete(route('branches.destroy', $feature))
            ->assertRedirect(route('branches.index', $repo))->assertSessionHas('error', 'La rama tiene Pull Requests abiertos.');

        $this->assertNotNull(Branch::find($main->id));
        $this->assertNotNull(Branch::find($feature->id));
    }

    public function test_observer_and_strangers_cannot_delete_branches(): void
    {
        $feature = app(RepositoryService::class)->createBranch($this->private, $this->owner, 'tmp', null);

        $this->signIn($this->observer);
        $this->delete(route('branches.destroy', $feature))->assertForbidden();

        $this->signIn($this->outsider);
        $this->delete(route('branches.destroy', $feature))->assertNotFound();

        $publicBranch = app(RepositoryService::class)->createBranch($this->public, $this->owner, 'tmp', null);
        $this->delete(route('branches.destroy', $publicBranch))->assertForbidden();

        $this->assertNotNull(Branch::find($feature->id));
    }
}
