<?php

namespace Tests\Feature\Web\Account;

use App\Models\Project;
use App\Models\Repository;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\ProjectService;
use App\Services\RepositoryService;

/** Monta proyectos de prueba a través de los servicios (igual que harían los usuarios). */
trait CreatesProjects
{
    protected User $owner;

    protected User $collaborator;

    protected User $observer;

    protected User $outsider;

    protected User $admin;

    protected Project $project;

    protected function setUpTeam(): void
    {
        $this->owner = User::factory()->create(['name' => 'Laura Propietaria']);
        $this->collaborator = User::factory()->create(['name' => 'Carlos Colaborador']);
        $this->observer = User::factory()->create(['name' => 'Olga Observadora']);
        $this->outsider = User::factory()->create(['name' => 'Pedro Ajeno']);
        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->forceFill(['is_admin' => true])->save();

        $this->project = $this->makeProject($this->owner, 'Proyecto DAM', 'Descripción **importante**');

        $members = app(MembershipService::class);
        $members->add($this->project, $this->owner, $this->collaborator->email, 'collaborator');
        $members->add($this->project, $this->owner, $this->observer->email, 'observer');
    }

    protected function makeProject(User $owner, string $name, ?string $description = null): Project
    {
        return app(ProjectService::class)->create($owner, ['name' => $name, 'description' => $description]);
    }

    protected function makeRepository(Project $project, User $owner, string $name, string $visibility = 'private', ?string $description = null): Repository
    {
        return app(RepositoryService::class)->create($project, $owner, [
            'name' => $name,
            'description' => $description,
            'visibility' => $visibility,
        ]);
    }

    /** Sesión web con una instancia nueva del usuario (sin cachés de roles). */
    protected function as(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user->fresh());
    }
}
