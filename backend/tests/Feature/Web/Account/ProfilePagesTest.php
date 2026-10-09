<?php

namespace Tests\Feature\Web\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilePagesTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    public function test_profile_page_shows_roles_summary(): void
    {
        $this->setUpTeam();

        $this->as($this->observer)->get('/profile')->assertOk()
            ->assertSee('Mi perfil')->assertSee($this->observer->email)
            ->assertSee('Proyecto DAM')->assertSee('Observador');
    }

    public function test_user_can_update_name_and_email(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->put('/profile', ['name' => 'Nombre Nuevo', 'email' => 'nuevo@codeflow.test'])
            ->assertRedirect(route('profile.edit'))->assertSessionHas('status');
        $this->assertSame('Nombre Nuevo', $user->fresh()->name);
        $this->assertSame('nuevo@codeflow.test', $user->fresh()->email);
        // El email nuevo hay que verificarlo de nuevo (código por correo)
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->from('/profile')->put('/profile', ['name' => 'X', 'email' => $other->email])->assertSessionHasErrors('email');
    }

    public function test_password_change_requires_current_password_and_revokes_tokens(): void
    {
        $user = User::factory()->create(['password' => 'antigua-123']);
        $user->createToken('android');

        $this->actingAs($user)->from('/profile')->put('/profile/password', [
            'current_password' => 'incorrecta', 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('antigua-123', $user->fresh()->password));

        $this->from('/profile')->put('/profile/password', [
            'current_password' => 'antigua-123', 'password' => 'corta', 'password_confirmation' => 'corta',
        ])->assertSessionHasErrors('password');

        $this->from('/profile')->put('/profile/password', [
            'current_password' => 'antigua-123', 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1',
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('nueva-clave-1', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertAuthenticatedAs($user);
    }
}
