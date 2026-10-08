<?php

namespace Tests\Feature\Web\Account;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResetLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_email_link_points_to_the_web_and_opens_the_form(): void
    {
        config(['app.url' => 'http://localhost:8080', 'app.frontend_url' => null]);
        Notification::fake();
        $user = User::factory()->create(['email' => 'ana@example.com']);

        $this->post('/forgot-password', ['email' => 'ana@example.com']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;
            $this->assertStringStartsWith('http://localhost:8080/reset-password?token=', $url);

            // El enlace lleva al formulario de nueva contraseña
            $path = substr($url, strlen('http://localhost:8080'));
            $this->get($path)->assertRedirect();

            return true;
        });
    }
}
