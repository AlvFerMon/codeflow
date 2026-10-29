<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Dispositivos y sesiones abiertas. */
class DevicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_lists_apps_and_closes_them(): void
    {
        $user = User::factory()->create(['password' => 'secreto-123']);
        $cli = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secreto-123', 'device_name' => 'codeflow-cli (portatil)'])->json('token');
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secreto-123', 'device_name' => 'codeflow-desktop (sobremesa)']);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secreto-123', 'device_name' => 'Android Pixel 8']);

        $apps = collect($this->withToken($cli)->getJson('/api/me/devices')->assertOk()->json('apps'))->keyBy('kind');
        $this->assertSame('Terminal (CLI) · portatil', $apps['cli']['name']);
        $this->assertTrue($apps['cli']['current']);
        $this->assertSame('App de escritorio · sobremesa', $apps['desktop']['name']);
        $this->assertSame('App Android · Pixel 8', $apps['android']['name']);

        // Cerrar una concreta y luego «todas las demás» (la actual sigue funcionando)
        $this->withToken($cli)->deleteJson("/api/me/devices/apps/{$apps['android']['id']}")->assertOk();
        $this->withToken($cli)->deleteJson('/api/me/devices/others')->assertOk()->assertJsonPath('message', 'Se ha cerrado 1 sesión.');
        $this->assertSame(1, $user->tokens()->count());
        $this->withToken($cli)->getJson('/api/me')->assertOk();

        // No se pueden cerrar las de otro usuario
        $other = User::factory()->create();
        $foreign = $other->createToken('codeflow-cli (x)')->accessToken->id;
        $this->withToken($cli)->deleteJson("/api/me/devices/apps/$foreign")->assertNotFound();
    }

    public function test_web_page_lists_browser_sessions_and_closes_others(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $user->createToken('codeflow-desktop (casa)');
        DB::table('sessions')->insert([
            'id' => str_repeat('a', 40), 'user_id' => $user->id, 'ip_address' => '10.0.0.7',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1',
            'payload' => '', 'last_activity' => now()->subHour()->timestamp,
        ]);

        $this->actingAs($user)->get(route('profile.devices'))->assertOk()
            ->assertSee('Safari en iPhone')->assertSee('10.0.0.7')
            ->assertSee('App de escritorio · casa');

        $this->actingAs($user)->delete(route('profile.devices.session', str_repeat('a', 40)))->assertRedirect(route('profile.devices'));
        $this->assertSame(0, DB::table('sessions')->where('id', str_repeat('a', 40))->count());

        $this->actingAs($user)->delete(route('profile.devices.others'))->assertRedirect();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_user_agent_names(): void
    {
        $this->assertSame('Chrome en Windows', DeviceService::describeAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36'));
        $this->assertSame('Edge en Windows', DeviceService::describeAgent('Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/129.0 Safari/537.36 Edg/129.0'));
        $this->assertSame('Firefox en Linux', DeviceService::describeAgent('Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0'));
        $this->assertSame('Chrome en Android', DeviceService::describeAgent('Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/129.0 Mobile Safari/537.36'));
        $this->assertSame('Navegador desconocido', DeviceService::describeAgent(''));
    }
}
