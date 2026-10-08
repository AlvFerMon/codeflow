<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $email, string $password)
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/auth/login', ['email' => $email, 'password' => $password]);
    }

    private function withTokenFresh(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    public function test_new_users_are_active_non_admins(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Lucía', 'email' => 'lucia@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertCreated();

        $user = User::where('email', 'lucia@example.com')->first();
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isAdmin());
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['email' => 'off@example.com']);
        $user->forceFill(['is_active' => false])->save();

        $this->login('off@example.com', 'password')
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Credenciales incorrectas.');
    }

    public function test_tokens_of_inactive_users_are_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('android')->plainTextToken;

        $this->withTokenFresh($token)->getJson('/api/me')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->withTokenFresh($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_change_password_revokes_other_tokens(): void
    {
        User::factory()->create(['email' => 'pepe@example.com']);

        $current = $this->login('pepe@example.com', 'password')->assertOk()->json('token');
        $other = $this->login('pepe@example.com', 'password')->assertOk()->json('token');

        $this->withTokenFresh($current)->putJson('/api/me/password', [
            'current_password' => 'mala', 'password' => 'nueva1234', 'password_confirmation' => 'nueva1234',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->withTokenFresh($current)->putJson('/api/me/password', [
            'current_password' => 'password', 'password' => 'nueva1234', 'password_confirmation' => 'nueva1234',
        ])->assertOk();

        $this->withTokenFresh($other)->getJson('/api/me')->assertUnauthorized();
        $this->withTokenFresh($current)->getJson('/api/me')->assertOk();

        $this->login('pepe@example.com', 'password')->assertStatus(422);
        $this->login('pepe@example.com', 'nueva1234')->assertOk();
    }
}
