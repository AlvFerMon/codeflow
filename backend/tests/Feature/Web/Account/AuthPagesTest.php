<?php

namespace Tests\Feature\Web\Account;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_pages_render(): void
    {
        $this->get('/register')->assertOk()->assertSee('Crear cuenta')->assertSee(route('login'));
        $this->get('/forgot-password')->assertOk()->assertSee('Recuperar contraseña');
        $this->get('/reset-password/abc?email=a@b.c')->assertOk()->assertSee('Nueva contraseña')->assertSee('value="abc"', false);
    }

    public function test_user_can_register_and_is_logged_in(): void
    {
        $this->post('/register', [
            'name' => 'Nueva Usuaria',
            'email' => 'nueva@codeflow.test',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect(route('home'));

        $user = User::where('email', 'nueva@codeflow.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('secreto123', $user->password));
        $this->assertFalse($user->isAdmin());
    }

    public function test_register_validates_duplicates_and_confirmation(): void
    {
        $existing = User::factory()->create();

        $this->from('/register')->post('/register', [
            'name' => 'X', 'email' => $existing->email, 'password' => 'secreto123', 'password_confirmation' => 'otra-cosa',
        ])->assertRedirect('/register')->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_logged_in_users_cannot_see_register(): void
    {
        $this->actingAs(User::factory()->create())->get('/register')->assertRedirect();
    }

    public function test_forgot_password_sends_link_with_generic_message(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')->assertSessionHas('status', fn ($s) => str_starts_with($s, 'Si el correo existe'));
        Notification::assertSentTo($user, ResetPassword::class);

        // Mismo mensaje si el correo no existe
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'nadie@codeflow.test'])
            ->assertSessionHas('status', fn ($s) => str_starts_with($s, 'Si el correo existe'));
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('android');
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email, 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('nueva-clave-1', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());

        $this->post('/login', ['email' => $user->email, 'password' => 'nueva-clave-1'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'antigua-123']);

        $this->from('/reset-password/malo')->post('/reset-password', [
            'token' => 'malo', 'email' => $user->email, 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1',
        ])->assertRedirect('/reset-password/malo')->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('antigua-123', $user->fresh()->password));
    }

    public function test_query_string_reset_link_redirects_to_form(): void
    {
        $this->get('/reset-password?token=tok123&email=a%40b.c')
            ->assertRedirect(route('password.reset', ['token' => 'tok123', 'email' => 'a@b.c']));
        $this->get('/reset-password')->assertRedirect(route('password.request'));
    }
}
