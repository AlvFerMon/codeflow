<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Cambiar el correo pide la contraseña actual: una sesión o un token robados no bastan para quedarse la cuenta. */
class EmailChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_email_change_requires_current_password(): void
    {
        $user = User::factory()->create(['email' => 'ana@codeflow.test']);

        $this->actingAs($user)->from('/profile')->put('/profile', ['name' => 'Ana', 'email' => 'atacante@mal.test'])
            ->assertSessionHasErrors('email_password');
        $this->from('/profile')->put('/profile', ['name' => 'Ana', 'email' => 'atacante@mal.test', 'email_password' => 'mala'])
            ->assertSessionHasErrors('email_password');
        $this->assertSame('ana@codeflow.test', $user->fresh()->email);

        // Cambiar solo el nombre no la pide
        $this->put('/profile', ['name' => 'Ana María', 'email' => 'ANA@codeflow.test'])->assertSessionHasNoErrors();
        $this->assertSame('Ana María', $user->fresh()->name);
        $this->assertSame('ana@codeflow.test', $user->fresh()->email);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_api_email_change_requires_current_password(): void
    {
        $user = User::factory()->create(['email' => 'ana@codeflow.test']);
        Sanctum::actingAs($user);

        $this->putJson('/api/me', ['email' => 'atacante@mal.test'])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertSame('ana@codeflow.test', $user->fresh()->email);

        $this->putJson('/api/me', ['email' => 'nuevo@codeflow.test', 'current_password' => 'password'])->assertOk();
        $this->assertSame('nuevo@codeflow.test', $user->fresh()->email);
        $this->putJson('/api/me', ['name' => 'Solo nombre'])->assertOk();
    }
}
