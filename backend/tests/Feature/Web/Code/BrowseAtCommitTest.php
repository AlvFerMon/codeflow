<?php

namespace Tests\Feature\Web\Code;

use App\Models\Branch;
use App\Services\VcsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Web\Account\CreatesProjects;
use Tests\TestCase;

/** U10: ver el código tal como estaba en un commit antiguo (solo lectura). */
class BrowseAtCommitTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    public function test_code_can_be_browsed_at_an_old_commit(): void
    {
        $this->setUpTeam();
        $repo = $this->makeRepository($this->project, $this->owner, 'web', 'public');
        $branch = Branch::where('repository_id', $repo->id)->where('name', 'main')->firstOrFail();
        $vcs = app(VcsService::class);
        $old = $vcs->commit($branch, $this->owner, 'Versión 1', [['path' => 'src/app.txt', 'content' => "version uno\n"]]);
        $vcs->commit($branch->fresh(), $this->owner, 'Versión 2', [['path' => 'src/app.txt', 'content' => "version dos\n"], ['path' => 'nuevo.txt', 'content' => "x\n"]]);

        $short = substr($old->sha, 0, 12);
        $this->as($this->owner);

        $this->get(route('repositories.show', [$repo, 'commit' => $short]))->assertOk()
            ->assertSee('Estás viendo el código tal como estaba en el commit')
            ->assertSee('src')->assertDontSee('nuevo.txt')
            ->assertDontSee('Subir archivos o carpetas')
            ->assertSee(route('repositories.show', [$repo, 'commit' => $short, 'path' => 'src']));

        $this->get(route('repositories.blob', [$repo, 'commit' => $short, 'path' => 'src/app.txt']))->assertOk()
            ->assertSee('version uno')->assertDontSee('version dos')->assertDontSee('Editar');
        $this->get(route('repositories.raw', [$repo, 'commit' => $short, 'path' => 'src/app.txt']))->assertOk()->assertSee('version uno');

        // La versión actual sigue intacta y el commit enlaza a esta vista
        $this->get(route('repositories.blob', [$repo, 'path' => 'src/app.txt']))->assertOk()->assertSee('version dos');
        $this->get(route('commits.show', [$repo, $old->sha]))->assertOk()->assertSee('Ver el código en este commit');

        // Commits inexistentes o de otro repositorio: 404
        $this->get(route('repositories.show', [$repo, 'commit' => 'abcdef1234']))->assertNotFound();
        $this->get(route('repositories.show', [$repo, 'commit' => 'zz']))->assertNotFound();
    }
}
