<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_see_the_download_links(): void
    {
        $this->get('/descargas')
            ->assertOk()
            ->assertSee('Descargar para Windows')
            ->assertSee(config('codeflow.downloads.windows'), false)
            ->assertSee(config('codeflow.downloads.cli'), false)
            ->assertSee('Próximamente'); // sin APK configurado
    }

    public function test_android_button_appears_when_configured(): void
    {
        config(['codeflow.downloads.android' => 'https://example.test/CodeFlow.apk']);

        $this->actingAs(User::factory()->create())
            ->get(route('downloads'))
            ->assertOk()
            ->assertSee('Descargar APK')
            ->assertSee('https://example.test/CodeFlow.apk', false)
            ->assertDontSee('Próximamente');
    }

    public function test_navigation_links_to_downloads(): void
    {
        $this->get('/')->assertOk()->assertSee(route('downloads'), false);
    }
}
