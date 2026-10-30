<?php

namespace Tests\Feature\Web\Account;

use App\Models\User;
use App\Notifications\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    /** Código enviado al usuario en el último correo (Notification::fake). */
    private function lastCode(User $user): string
    {
        $code = null;
        Notification::assertSentTo($user, VerificationCode::class, function (VerificationCode $n) use (&$code) {
            $code = $n->code;

            return true;
        });

        return $code;
    }

    public function test_registration_sends_a_six_digit_code_in_spanish(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Nueva Usuaria', 'email' => 'nueva@codeflow.test',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'nueva@codeflow.test')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        $code = $this->lastCode($user);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $mail = (new VerificationCode($code, 30))->toMail($user);
        $this->assertSame('Tu código de verificación de CodeFlow: '.$code, $mail->subject);
        $this->assertStringContainsString($code, implode(' ', array_map('strval', $mail->introLines)));

        // Solo se guarda el hash del código
        $this->assertNotSame($code, $user->email_verification_code);
        $this->get(route('verification.notice'))->assertOk()
            ->assertSee('Esperando la verificación de tu email')->assertSee('nueva@codeflow.test')
            ->assertSee('Código de verificación')->assertSee('Reenviar código');
    }

    public function test_unverified_user_sees_nothing_but_the_verification_screen(): void
    {
        $this->setUpTeam();
        $public = $this->makeRepository($this->project, $this->owner, 'web-publica', 'public');
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        foreach (['/', '/projects', '/notifications', '/explore', '/profile', route('repositories.show', $public)] as $url) {
            $this->get($url)->assertRedirect(route('verification.notice'));
        }

        // Puede cerrar sesión; y sin sesión todo vuelve a ser como para cualquier visitante
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('repositories.show', $public))->assertOk();
        $this->get('/explore')->assertOk();
    }

    public function test_correct_code_verifies_and_gives_full_access(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $user->sendEmailVerificationNotification();
        $code = $this->lastCode($user);

        $this->actingAs($user)->post(route('verification.verify'), ['code' => substr($code, 0, 3).' '.substr($code, 3)])
            ->assertRedirect(route('home'))->assertSessionHas('status');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull($user->fresh()->email_verification_code);
        $this->actingAs($user->fresh())->get('/')->assertOk()->assertSee('Te toca');
    }

    public function test_wrong_codes_are_limited_and_codes_expire(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $user->sendEmailVerificationNotification();
        $code = $this->lastCode($user);
        $wrong = $code === '111111' ? '222222' : '111111';

        $this->actingAs($user)->from(route('verification.notice'))
            ->post(route('verification.verify'), ['code' => $wrong])
            ->assertSessionHasErrors(['code' => 'El código no es correcto. Te quedan 4 intentos.']);

        for ($i = 0; $i < 4; $i++) {
            $this->post(route('verification.verify'), ['code' => $wrong]);
        }
        // Agotados los intentos, ni el código correcto sirve: hay que pedir otro
        $this->post(route('verification.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());

        // Un código nuevo funciona, pero no si ha caducado
        $this->post(route('verification.send'))->assertSessionHas('status');
        $fresh = $this->lastCode($user);
        $this->travel(31)->minutes();
        $this->post(route('verification.verify'), ['code' => $fresh])
            ->assertSessionHasErrors(['code' => 'El código ha caducado. Pide uno nuevo con «Reenviar código».']);
    }

    public function test_user_can_fix_a_mistyped_email_from_the_verification_screen(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['email' => 'mal@codeflow.tset']);

        $this->actingAs($user)->from(route('verification.notice'))
            ->put(route('verification.email'), ['email' => 'bien@codeflow.test'])
            ->assertRedirect(route('verification.notice'));

        $this->assertSame('bien@codeflow.test', $user->fresh()->email);
        Notification::assertSentTo($user->fresh(), VerificationCode::class);

        // Cambiar el email de una cuenta verificada obliga a verificarlo de nuevo
        $verified = User::factory()->create(['name' => 'Ana']);
        $this->actingAs($verified)->put('/profile', ['name' => 'Ana', 'email' => 'ana.nueva@codeflow.test', 'email_password' => 'password']);
        $this->assertFalse($verified->fresh()->hasVerifiedEmail());
    }

    public function test_api_register_verify_with_code_and_403_until_then(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Android', 'email' => 'android@codeflow.test',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ])->assertCreated()->assertJsonPath('user.email_verified_at', null)
            ->assertJsonMissingPath('user.email_verification_code');

        $user = User::where('email', 'android@codeflow.test')->firstOrFail();
        $code = $this->lastCode($user);
        $token = $response->json('token');

        $this->withToken($token)->getJson('/api/me')->assertOk();
        $this->withToken($token)->getJson('/api/projects')->assertForbidden()->assertJsonPath('code', 'email_not_verified');
        $this->withToken($token)->postJson('/api/auth/email/verify', ['code' => '000000'])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->withToken($token)->postJson('/api/auth/email/verify', ['code' => $code])->assertOk()->assertJsonPath('message', 'Email verificado.');

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/projects')->assertOk();
    }

    public function test_existing_accounts_are_marked_as_verified_by_migration(): void
    {
        $migration = require database_path('migrations/2026_10_09_000001_verify_existing_users.php');
        $user = User::factory()->unverified()->create();
        $migration->up();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
