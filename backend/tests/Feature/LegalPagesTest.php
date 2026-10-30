<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148';

    public function test_legal_pages_are_public_and_filled_in(): void
    {
        config(['codeflow.legal.email' => 'contacto@codeflow.test']);

        foreach (['privacidad' => 'Política de privacidad', 'cookies' => 'Política de cookies', 'terminos' => 'Condiciones de uso', 'aviso-legal' => 'Aviso legal'] as $slug => $title) {
            $html = $this->get("/legal/$slug")->assertOk()->assertSee($title)->getContent();
            $this->assertDoesNotMatchRegularExpression('/\{[a-z_]+\}/', strip_tags($html), "Quedan marcadores sin rellenar en /legal/$slug");
        }
        $this->get('/legal/privacidad')->assertSee('contacto@codeflow.test')->assertSee('aepd.es')->assertSee('<table', false);
        $this->get('/legal/cookies')->assertSee(config('session.cookie'));
        $this->get('/legal/otra')->assertNotFound();

        config(['codeflow.legal.email' => null]);
        $this->get('/legal/privacidad')->assertSee('lo publicaremos aquí en breve')->assertSee('Irlanda');
    }

    public function test_reachable_from_phones_and_unverified_accounts(): void
    {
        $this->withHeader('User-Agent', self::IPHONE)->get('/legal/privacidad')->assertOk();
        $this->actingAs(User::factory()->unverified()->create())->get('/legal/terminos')->assertOk();
    }

    public function test_register_and_footer_link_to_legal_pages(): void
    {
        $this->get('/register')->assertSee(route('legal', 'privacidad'))->assertSee('14 años');
        $this->actingAs(User::factory()->create())->get('/')->assertSee(route('legal', 'aviso-legal'));
    }
}
