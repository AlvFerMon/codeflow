<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\PullRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/** Tutorial de bienvenida: visita guiada + lista «Primeros pasos». */
class OnboardingTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    public function test_a_new_account_sees_the_tour_and_an_empty_checklist(): void
    {
        $this->actingAsUser(User::factory()->create());

        $this->getJson('/api/me/onboarding')->assertOk()
            ->assertJsonPath('tour.status', 'pending')
            ->assertJsonPath('tour.show', true)
            ->assertJsonPath('tour.step', 0)
            ->assertJsonPath('checklist.visible', true)
            ->assertJsonPath('checklist.done', 0)
            ->assertJsonPath('checklist.required_total', 7)
            ->assertJsonPath('sample_project.id', null);
    }

    public function test_the_tour_can_be_followed_finished_skipped_and_restarted(): void
    {
        $this->actingAsUser(User::factory()->create());

        $this->postJson('/api/me/onboarding/tour/progress', ['step' => 3])->assertOk()
            ->assertJsonPath('tour.status', 'in_progress')->assertJsonPath('tour.step', 3)->assertJsonPath('tour.show', true);
        $this->postJson('/api/me/onboarding/tour/progress', ['step' => 99])->assertOk()
            ->assertJsonPath('tour.step', count(config('onboarding.tour')) - 1);

        $this->postJson('/api/me/onboarding/tour/complete')->assertOk()
            ->assertJsonPath('tour.status', 'completed')->assertJsonPath('tour.show', false);

        $this->postJson('/api/me/onboarding/restart')->assertOk()
            ->assertJsonPath('tour.status', 'pending')->assertJsonPath('tour.show', true)->assertJsonPath('tour.step', 0);

        $this->postJson('/api/me/onboarding/tour/skip')->assertOk()
            ->assertJsonPath('tour.status', 'skipped')->assertJsonPath('tour.show', false);
    }

    public function test_checklist_items_are_detected_from_real_actions(): void
    {
        $owner = User::factory()->create();
        $mate = User::factory()->create();
        $ctx = $this->buildProject($owner, [[$mate, 'collaborator']]);

        $this->postJson("/api/branches/{$ctx['main']}/commits", [
            'message' => 'Cambio', 'changes' => [['path' => 'a.txt', 'content' => 'a']],
        ])->assertCreated();

        $items = collect($this->getJson('/api/me/onboarding')->assertOk()->json('checklist.items'))->keyBy('id');

        foreach (['create_project', 'create_repository', 'first_commit', 'invite_member'] as $id) {
            $this->assertTrue($items[$id]['done'], "$id debería estar hecho");
        }
        foreach (['create_branch', 'open_issue', 'open_pull_request', 'connect_app'] as $id) {
            $this->assertFalse($items[$id]['done'], "$id no debería estar hecho");
        }

        // Lo detectado se conserva aunque se borre el proyecto
        $this->deleteJson("/api/projects/{$ctx['project']}")->assertSuccessful();
        $this->getJson('/api/me/onboarding')->assertJsonPath('checklist.done', 4);
    }

    public function test_connecting_an_app_is_detected_by_its_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('codeflow-cli (portatil)');
        $this->actingAsUser($user);

        $items = collect($this->getJson('/api/me/onboarding')->json('checklist.items'))->keyBy('id');
        $this->assertTrue($items['connect_app']['done']);
        $this->assertTrue($items['connect_app']['optional']);
    }

    public function test_the_checklist_can_be_hidden_for_a_few_days_and_comes_back(): void
    {
        $this->actingAsUser(User::factory()->create());

        $this->postJson('/api/me/onboarding/checklist/hide')->assertOk()->assertJsonPath('checklist.visible', false);
        $this->travel(config('onboarding.checklist_snooze_days') + 1)->days();
        $this->getJson('/api/me/onboarding')->assertJsonPath('checklist.visible', true);

        $this->postJson('/api/me/onboarding/checklist/hide')->assertOk();
        $this->postJson('/api/me/onboarding/checklist/show')->assertOk()->assertJsonPath('checklist.visible', true);
    }

    public function test_sample_project_is_created_once_and_does_not_tick_the_checklist(): void
    {
        $user = User::factory()->create();
        $this->actingAsUser($user);

        $id = $this->postJson('/api/me/onboarding/sample-project')->assertCreated()
            ->assertJsonPath('checklist.done', 0)
            ->json('sample_project.id');

        $project = Project::findOrFail($id);
        $repository = $project->repositories()->firstOrFail();
        $this->assertSame($user->id, $project->owner_id);
        $this->assertSame(['main', 'mejora-portada'], $repository->branches()->orderBy('name')->pluck('name')->all());
        $this->assertSame(1, Issue::where('repository_id', $repository->id)->count());
        $pr = PullRequest::where('repository_id', $repository->id)->firstOrFail();
        $this->assertSame('open', $pr->status);

        // Una segunda vez devuelve el mismo
        $this->postJson('/api/me/onboarding/sample-project')->assertCreated()->assertJsonPath('sample_project.id', $id);
        $this->assertSame(1, Project::where('owner_id', $user->id)->count());

        // Y se puede fusionar el PR de ejemplo
        $this->postJson("/api/pull-requests/{$pr->id}/merge")->assertSuccessful();
    }

    public function test_web_routes_and_unverified_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('onboarding.skip'))->assertRedirect();
        $this->assertSame('skipped', $user->fresh()->onboarding['tour_status']);

        $this->actingAs($user)->post(route('onboarding.sample'))
            ->assertRedirect(route('projects.show', $user->fresh()->onboarding['sample_project_id']));

        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)->get(route('onboarding.show'))->assertRedirect(route('verification.notice'));

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/me/onboarding')->assertStatus(401);
    }

    public function test_home_page_includes_the_tour_and_the_checklist_for_new_accounts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertOk()
            ->assertSee('id="cf-onboarding"', false)
            ->assertSee('data-tour="primeros-pasos"', false)
            ->assertSee('data-tour="nav-projects"', false)
            ->assertSee('Primeros pasos');

        // Tras terminar la visita ya no se incluye; la lista sigue hasta completarla
        $this->actingAs($user)->post(route('onboarding.complete'));
        $this->actingAs($user)->get('/')->assertOk()
            ->assertDontSee('id="cf-onboarding"', false)
            ->assertSee('Primeros pasos');

        // Las cuentas antiguas (migración) no ven la visita
        $old = User::factory()->create(['onboarding' => ['tour_status' => 'completed', 'legacy' => true]]);
        $this->actingAs($old)->get('/')->assertOk()->assertDontSee('id="cf-onboarding"', false);
    }
}
