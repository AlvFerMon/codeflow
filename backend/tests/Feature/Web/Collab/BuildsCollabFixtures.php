<?php

namespace Tests\Feature\Web\Collab;

use App\Models\Branch;
use App\Models\Issue;
use App\Models\Project;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\User;
use App\Services\IssueService;
use App\Services\MembershipService;
use App\Services\ProjectService;
use App\Services\PullRequestService;
use App\Services\RepositoryService;
use App\Support\Roles;

/** Proyecto de prueba para las páginas web de issues y PRs, montado con los servicios de la aplicación. */
trait BuildsCollabFixtures
{
    protected User $owner;

    protected User $dev;

    protected User $observer;

    protected User $outsider;

    protected Project $project;

    protected Repository $repo;

    protected Branch $main;

    protected function buildCollabProject(string $visibility = 'private'): void
    {
        $this->owner = User::factory()->create(['name' => 'Laura Propietaria']);
        $this->dev = User::factory()->create(['name' => 'Carlos Colaborador']);
        $this->observer = User::factory()->create(['name' => 'Olga Observadora']);
        $this->outsider = User::factory()->create(['name' => 'Persona Ajena']);

        $this->project = app(ProjectService::class)->create($this->owner, ['name' => 'Proyecto DAM']);
        $members = app(MembershipService::class);
        $members->add($this->project, $this->owner, $this->dev->email, Roles::COLLABORATOR);
        $members->add($this->project, $this->owner, $this->observer->email, Roles::OBSERVER);

        $this->repo = app(RepositoryService::class)->create($this->project, $this->owner, [
            'name' => 'app', 'visibility' => $visibility,
        ]);
        $this->main = Branch::where('repository_id', $this->repo->id)->where('name', 'main')->firstOrFail();
    }

    protected function makeIssue(User $author, array $data = []): Issue
    {
        return app(IssueService::class)->create($this->repo, $author->fresh(), $data + ['title' => 'Error en el login']);
    }

    /** Rama a partir de main con un commit que añade (o cambia) un archivo. */
    protected function makeBranch(string $name, string $path = 'src/Login.php', string $content = "<?php\n// login\n"): Branch
    {
        $service = app(RepositoryService::class);
        $branch = $service->createBranch($this->repo, $this->dev->fresh(), $name, $this->main->id);
        $service->commitChanges($branch, $this->dev->fresh(), "Cambios en {$name}", [['path' => $path, 'content' => $content]]);

        return $branch->fresh();
    }

    protected function makePullRequest(Branch $source, ?User $author = null, array $reviewers = []): PullRequest
    {
        return app(PullRequestService::class)->open($this->repo, ($author ?? $this->dev)->fresh(), [
            'title' => 'Login de usuarios',
            'source_branch_id' => $source->id,
            'target_branch_id' => $this->main->id,
            'reviewers' => $reviewers,
        ]);
    }

    /** Sesión web con una instancia nueva del usuario (sin caché de roles). */
    protected function actAs(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user->fresh());
    }

    protected function asGuest(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }

    protected function latestNotificationFor(User $user): ?array
    {
        return $user->fresh()->notifications()->latest()->first()?->data;
    }
}
