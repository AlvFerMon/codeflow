<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Preferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/** Preferencias de accesibilidad guardadas en la cuenta. */
class PreferencesTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    public function test_defaults_and_partial_updates_through_the_api(): void
    {
        $this->actingAsUser(User::factory()->create());

        $this->getJson('/api/me/preferences')->assertOk()->assertExactJson(Preferences::DEFAULTS);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('preferences.font_size', 'normal');

        $this->patchJson('/api/me/preferences', ['font_size' => 'larger', 'high_contrast' => true])->assertOk()
            ->assertJsonPath('font_size', 'larger')
            ->assertJsonPath('high_contrast', true)
            ->assertJsonPath('theme', 'system');

        // Cambiar otra cosa no borra lo anterior
        $this->patchJson('/api/me/preferences', ['theme' => 'dark'])->assertOk()
            ->assertJsonPath('font_size', 'larger')->assertJsonPath('theme', 'dark');

        $this->patchJson('/api/me/preferences', ['font_size' => 'gigante'])->assertStatus(422);
        $this->patchJson('/api/me/preferences', ['is_admin' => true])->assertOk();
        $this->assertArrayNotHasKey('is_admin', $this->getJson('/api/me/preferences')->json());
    }

    public function test_other_users_preferences_are_not_exposed(): void
    {
        $owner = User::factory()->create(['preferences' => ['font_size' => 'large']]);
        $this->actingAsUser($owner);
        $project = $this->postJson('/api/projects', ['name' => 'P'])->assertCreated()->json('id');

        $this->assertStringNotContainsString('font_size', $this->getJson("/api/projects/$project/members")->getContent());
    }

    public function test_web_form_saves_and_the_layout_applies_them(): void
    {
        $user = User::factory()->create();

        // Formulario: las casillas sin marcar cuentan como «no»
        $this->actingAs($user)->patch(route('profile.preferences'), [
            '_form' => 1, 'theme' => 'dark', 'font_size' => 'large', 'high_contrast' => '1', 'wrap_code' => '1',
        ])->assertRedirect(route('profile.edit'));
        $this->assertTrue(Preferences::for($user->fresh())['high_contrast']);

        $this->actingAs($user->fresh())->get('/')->assertOk()
            ->assertSee('data-font-size="large"', false)
            ->assertSee('data-contrast="high"', false)
            ->assertSee('data-wrap-code="true"', false)
            ->assertDontSee('data-motion=', false)
            ->assertSee('var saved = "dark"', false);

        $this->actingAs($user->fresh())->patch(route('profile.preferences'), ['_form' => 1, 'theme' => 'dark', 'font_size' => 'normal']);
        $prefs = Preferences::for($user->fresh());
        $this->assertFalse($prefs['high_contrast']);
        $this->assertSame('normal', $prefs['font_size']);

        $this->actingAs($user->fresh())->get(route('profile.edit'))->assertOk()->assertSee('Accesibilidad y apariencia');
    }

    public function test_guests_get_no_preference_attributes(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-preferences-url', false)->assertSee('var saved = null', false);
    }
}
