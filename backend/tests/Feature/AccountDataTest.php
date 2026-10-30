<?php

namespace Tests\Feature;

use App\Models\Commit;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/** RGPD: descargar mis datos y borrar mi cuenta. */
class AccountDataTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    public function test_export_contains_my_data_only(): void
    {
        $ana = User::factory()->create(['name' => 'Ana']);
        $otro = User::factory()->create();
        $ctx = $this->buildProject($ana);
        $this->postJson("/api/repositories/{$ctx['repo']}/issues", ['title' => 'Mi issue'])->assertCreated();
        $this->buildProject($otro, [], 'ajeno');

        $this->actingAsUser($ana);
        $response = $this->getJson('/api/me/export')->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $response->assertJsonPath('account.email', $ana->email)
            ->assertJsonPath('issues.0.title', 'Mi issue')
            ->assertJsonCount(1, 'projects');
        $this->assertNotEmpty($response->json('commits'));

        $this->actingAs($ana->fresh())->get('/profile/export')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_delete_requires_password_and_confirmation(): void
    {
        $ana = User::factory()->create();
        Sanctum::actingAs($ana);

        $this->deleteJson('/api/me', ['password' => 'mala', 'confirm' => 'BORRAR'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->deleteJson('/api/me', ['password' => 'password', 'confirm' => 'borrar'])->assertUnprocessable()->assertJsonValidationErrors('confirm');
        $this->assertModelExists($ana);
    }

    public function test_delete_removes_solo_projects_and_keeps_shared_work(): void
    {
        $ana = User::factory()->create();
        $luis = User::factory()->create();
        $solo = $this->buildProject($ana, [], 'solo');
        $shared = $this->buildProject($luis, [[$ana, 'collaborator']], 'compartido');
        // Ana hace un commit en el proyecto compartido
        $this->actingAsUser($ana);
        $this->postJson("/api/branches/{$shared['main']}/commits", ['message' => 'Aporte de Ana', 'changes' => [['path' => 'a.txt', 'content' => 'hola']]])->assertCreated();

        $this->deleteJson('/api/me', ['password' => 'password', 'confirm' => 'BORRAR'])->assertOk();

        $this->assertModelMissing($ana);
        $this->assertNull(Project::withTrashed()->find($solo['project']));
        $this->assertNotNull(Project::find($shared['project']));
        $this->assertNull(Commit::where('repository_id', $shared['repo'])->latest('id')->first()->author_id); // «usuario eliminado»
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $ana->id)->count());
    }

    public function test_sole_owner_of_shared_project_must_hand_it_over_first(): void
    {
        $ana = User::factory()->create();
        $luis = User::factory()->create();
        $ctx = $this->buildProject($ana, [[$luis, 'collaborator']]);

        $this->actingAsUser($ana);
        $this->deleteJson('/api/me', ['password' => 'password', 'confirm' => 'BORRAR'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertModelExists($ana);

        // Con otro propietario sí se puede, y el proyecto pasa a él
        ProjectMember::where('project_id', $ctx['project'])->where('user_id', $luis->id)->update(['role' => 'owner']);
        $this->actingAsUser($ana);
        $this->deleteJson('/api/me', ['password' => 'password', 'confirm' => 'BORRAR'])->assertOk();
        $this->assertSame($luis->id, Project::find($ctx['project'])->owner_id);
    }

    public function test_web_delete_logs_out(): void
    {
        $ana = User::factory()->create();

        $this->actingAs($ana)->from('/profile')->delete('/profile', ['password' => 'mala', 'confirm' => 'BORRAR'])
            ->assertRedirect('/profile')->assertSessionHasErrorsIn('deleteAccount', 'password');
        $this->get('/profile')->assertOk()->assertSee('Borrar mi cuenta')->assertSee('Descargar mis datos');

        $this->delete('/profile', ['password' => 'password', 'confirm' => 'BORRAR'])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertModelMissing($ana);
    }

    public function test_last_admin_cannot_delete_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);

        $this->deleteJson('/api/me', ['password' => 'password', 'confirm' => 'BORRAR'])->assertUnprocessable();
        $this->assertModelExists($admin);
    }
}
