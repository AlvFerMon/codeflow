<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

/** Ayudas para montar proyectos de prueba a través de la API. */
trait BuildsProjects
{
    /**
     * Crea un proyecto con un repositorio (rama main + README) y añade miembros.
     *
     * @param  array<int,array{0:User,1:string}>  $members  [usuario, rol]
     * @return array{project:int,repo:int,main:int}
     */
    protected function buildProject(User $owner, array $members = [], string $repoName = 'app'): array
    {
        $this->actingAsUser($owner);

        $project = $this->postJson('/api/projects', ['name' => 'Proyecto DAM'])->assertCreated()->json('id');

        foreach ($members as [$user, $role]) {
            $this->postJson("/api/projects/$project/members", ['email' => $user->email, 'role' => $role])->assertCreated();
        }

        $repo = $this->postJson("/api/projects/$project/repositories", ['name' => $repoName])->assertCreated()->json('id');
        $main = collect($this->getJson("/api/repositories/$repo/branches")->assertOk()->json('data'))->firstWhere('name', 'main')['id'];

        return ['project' => $project, 'repo' => $repo, 'main' => $main];
    }

    /** Actúa como el usuario con una instancia nueva (sin cachés de roles de peticiones anteriores). */
    protected function actingAsUser(User $user): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user->fresh());
    }

    protected function asGuest(): void
    {
        $this->app['auth']->forgetGuards();
    }

    protected function requireVisibilityColumn(): void
    {
        if (! Schema::hasColumn('repositories', 'visibility')) {
            $this->markTestSkipped('La columna repositories.visibility la añade la rama del motor VCS; se probará tras la integración.');
        }
    }

    protected function makePublic(int $repositoryId): void
    {
        $this->requireVisibilityColumn();

        DB::table('repositories')->where('id', $repositoryId)->update(['visibility' => 'public']);
    }

    /** Crea una rama con un commit y abre un PR hacia main. Devuelve [rama, pr]. */
    protected function openPullRequest(array $ctx, string $branch = 'feature/x'): array
    {
        $feature = $this->postJson("/api/repositories/{$ctx['repo']}/branches", ['name' => $branch, 'from_branch_id' => $ctx['main']])
            ->assertCreated()->json('id');

        $this->postJson("/api/branches/$feature/commits", [
            'message' => 'Cambios en '.$branch,
            'changes' => [['path' => 'src/'.str_replace('/', '_', $branch).'.php', 'content' => "<?php\n// ok\n"]],
        ])->assertCreated();

        $pr = $this->postJson("/api/repositories/{$ctx['repo']}/pull-requests", [
            'title' => 'PR '.$branch, 'source_branch_id' => $feature, 'target_branch_id' => $ctx['main'],
        ])->assertCreated()->json('id');

        return [$feature, $pr];
    }
}
