<?php

namespace Tests\Feature\Web\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPagesTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTeam();
    }

    public function test_members_page_lists_members_and_controls_only_for_owner(): void
    {
        $url = route('members.index', $this->project);

        $this->as($this->owner)->get($url)->assertOk()
            ->assertSee('Carlos Colaborador')->assertSee($this->observer->email)
            ->assertSee('Propietario')->assertSee('Añadir miembro')->assertSee('Quitar');

        $this->as($this->collaborator)->get($url)->assertOk()
            ->assertSee('Laura Propietaria')->assertDontSee('Añadir al proyecto')->assertDontSee('Quitar');

        $this->as($this->outsider)->get($url)->assertNotFound();
    }

    public function test_owner_adds_observer_who_can_then_view_project(): void
    {
        $this->as($this->outsider)->get(route('projects.show', $this->project))->assertNotFound();

        $this->as($this->owner)->from(route('members.index', $this->project))
            ->post(route('members.store', $this->project), ['email' => $this->outsider->email, 'role' => 'observer'])
            ->assertRedirect(route('members.index', $this->project))->assertSessionHas('status');

        $this->assertSame('observer', $this->outsider->fresh()->roleIn($this->project));
        $this->as($this->outsider)->get(route('projects.show', $this->project))->assertOk();
        $this->assertSame(1, $this->outsider->notifications()->where('data->event', 'member.added')->count());
    }

    public function test_non_owners_cannot_manage_members(): void
    {
        $newbie = User::factory()->create();

        $this->as($this->collaborator)->post(route('members.store', $this->project), ['email' => $newbie->email, 'role' => 'observer'])->assertForbidden();
        $this->as($this->observer)->put(route('members.update', [$this->project, $this->collaborator]), ['role' => 'observer'])->assertForbidden();
        $this->as($this->collaborator)->delete(route('members.destroy', [$this->project, $this->observer]))->assertForbidden();
        $this->as($this->outsider)->post(route('members.store', $this->project), ['email' => $newbie->email, 'role' => 'observer'])->assertNotFound();

        $this->assertNull($newbie->fresh()->roleIn($this->project));
    }

    public function test_business_errors_are_shown_nicely(): void
    {
        $this->as($this->owner)->from(route('members.index', $this->project));

        $this->post(route('members.store', $this->project), ['email' => 'nadie@codeflow.test', 'role' => 'observer'])
            ->assertSessionHasErrors(['email' => 'No existe ningún usuario con ese correo.']);
        $this->post(route('members.store', $this->project), ['email' => $this->collaborator->email, 'role' => 'observer'])
            ->assertSessionHasErrors(['email' => 'El usuario ya es miembro del proyecto.']);
        $this->post(route('members.store', $this->project), ['email' => $this->outsider->email, 'role' => 'owner'])
            ->assertSessionHasErrors('role');

        $this->delete(route('members.destroy', [$this->project, $this->owner]))
            ->assertRedirect(route('members.index', $this->project))
            ->assertSessionHas('error', 'No se puede expulsar al propietario del proyecto.');
        $this->put(route('members.update', [$this->project, $this->owner]), ['role' => 'observer'])
            ->assertSessionHas('error', 'No se puede cambiar el rol del propietario del proyecto.');
    }

    public function test_owner_changes_role_and_removes_member(): void
    {
        $this->as($this->owner)->from(route('members.index', $this->project))
            ->put(route('members.update', [$this->project, $this->observer]), ['role' => 'collaborator'])
            ->assertSessionHas('status');
        $this->assertSame('collaborator', $this->observer->fresh()->roleIn($this->project));

        $this->delete(route('members.destroy', [$this->project, $this->collaborator]))->assertSessionHas('status');
        $this->assertNull($this->collaborator->fresh()->roleIn($this->project));
        $this->as($this->collaborator)->get(route('projects.show', $this->project))->assertNotFound();
    }
}
