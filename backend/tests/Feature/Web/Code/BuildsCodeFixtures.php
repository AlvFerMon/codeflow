<?php

namespace Tests\Feature\Web\Code;

use App\Models\Branch;
use App\Models\Project;
use App\Models\Repository;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\ProjectService;
use App\Services\RepositoryService;
use App\Support\Roles;

/** Proyecto con un repositorio privado y otro público, y usuarios con cada rol. */
trait BuildsCodeFixtures
{
    protected User $owner;

    protected User $collaborator;

    protected User $observer;

    protected User $outsider;

    protected Project $project;

    protected Repository $private;

    protected Repository $public;

    protected function buildFixtures(): void
    {
        $this->owner = User::factory()->create(['name' => 'Laura']);
        $this->collaborator = User::factory()->create(['name' => 'Carlos']);
        $this->observer = User::factory()->create(['name' => 'Olga']);
        $this->outsider = User::factory()->create(['name' => 'Ajeno']);

        $this->project = app(ProjectService::class)->create($this->owner, ['name' => 'Proyecto DAM']);
        app(MembershipService::class)->add($this->project, $this->owner, $this->collaborator->email, Roles::COLLABORATOR);
        app(MembershipService::class)->add($this->project, $this->owner, $this->observer->email, Roles::OBSERVER);

        $repositories = app(RepositoryService::class);
        $this->private = $repositories->create($this->project, $this->owner, ['name' => 'api', 'visibility' => 'private']);
        $repositories->commitChanges($this->branch($this->private), $this->owner, "Estructura inicial\n\nCon controlador y docs.", [
            ['path' => 'src/Controllers/UserController.php', 'content' => "<?php\n\nclass UserController {}\n"],
            ['path' => 'docs/.keep', 'content' => ''],
            ['path' => 'docs/README.md', 'content' => "# Documentación\n\nTexto **importante**.\n"],
        ]);

        $this->public = $repositories->create($this->project, $this->owner, ['name' => 'web', 'visibility' => 'public']);
    }

    protected function branch(Repository $repository, string $name = 'main'): Branch
    {
        return Branch::where('repository_id', $repository->id)->where('name', $name)->firstOrFail();
    }

    /** Inicia sesión web con una instancia nueva (sin cachés de roles). */
    protected function signIn(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user->fresh(), 'web');
    }

    protected function asGuestUser(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }
}
