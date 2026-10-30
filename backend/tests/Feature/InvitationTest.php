<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Notifications\ProjectActivityNotification;
use App\Notifications\ProjectInvitationNotification;
use App\Notifications\VerificationCode;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/** Invitar por correo a quien aún no tiene cuenta. */
class InvitationTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    private function inviteViaApi(User $owner, string $email = 'Nueva@Ejemplo.com', string $role = 'collaborator'): array
    {
        $ctx = $this->buildProject($owner);
        $this->postJson("/api/projects/{$ctx['project']}/members", ['email' => $email, 'role' => $role])
            ->assertStatus(202)->assertJsonPath('invitation.email', mb_strtolower($email));

        return $ctx;
    }

    public function test_inviting_an_unknown_email_sends_a_mail_with_a_link(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $ctx = $this->inviteViaApi($owner);

        $url = null;
        Notification::assertSentTo(new AnonymousNotifiable, ProjectInvitationNotification::class, function ($n, $channels, $notifiable) use (&$url) {
            $url = $n->url;

            return $notifiable->routes['mail'] === 'nueva@ejemplo.com';
        });
        $this->assertStringContainsString('/invitations/', $url);

        // El token solo va en el correo: en la base de datos se guarda su hash
        $token = basename($url);
        $this->assertSame(ProjectInvitation::hashToken($token), ProjectInvitation::firstOrFail()->token_hash);

        $this->getJson("/api/projects/{$ctx['project']}/invitations")->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.token_hash');

        // Invitar otra vez renueva la misma invitación (no duplica)
        $this->postJson("/api/projects/{$ctx['project']}/members", ['email' => 'nueva@ejemplo.com', 'role' => 'observer'])->assertStatus(202);
        $this->assertSame(1, ProjectInvitation::count());
        $this->assertSame('observer', ProjectInvitation::first()->role);
    }

    public function test_the_invitation_is_accepted_when_the_new_account_verifies_its_email(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $ctx = $this->inviteViaApi($owner);

        $new = User::factory()->unverified()->create(['email' => 'nueva@ejemplo.com']);
        $this->assertNull($new->roleIn(Project::find($ctx['project'])));

        // Verificación por código
        app(EmailVerificationService::class)->send($new);
        $code = null;
        Notification::assertSentTo($new, VerificationCode::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });
        app(EmailVerificationService::class)->verify($new->fresh(), $code);

        $this->assertSame('collaborator', $new->fresh()->roleIn(Project::find($ctx['project'])));
        $this->assertNotNull(ProjectInvitation::first()->accepted_at);
        $this->assertSame(0, ProjectInvitation::pending()->count());

        // Quien invitó recibe un aviso
        Notification::assertSentTo($owner, ProjectActivityNotification::class);
    }

    public function test_api_login_accepts_pending_invitations_and_expired_ones_are_ignored(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $ctx = $this->inviteViaApi($owner);
        $this->inviteViaApi($owner, 'otra@ejemplo.com');
        ProjectInvitation::where('email', 'otra@ejemplo.com')->update(['expires_at' => now()->subDay()]);

        // Cuentas creadas por otra vía (p. ej. seed) con el correo ya verificado
        $user = User::factory()->create(['email' => 'nueva@ejemplo.com', 'password' => 'secreto-123']);
        $late = User::factory()->create(['email' => 'otra@ejemplo.com', 'password' => 'secreto-123']);
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/auth/login', ['email' => 'nueva@ejemplo.com', 'password' => 'secreto-123'])->assertOk();
        $this->postJson('/api/auth/login', ['email' => 'otra@ejemplo.com', 'password' => 'secreto-123'])->assertOk();

        $project = Project::find($ctx['project']);
        $this->assertSame('collaborator', $user->fresh()->roleIn($project));
        $this->assertNull($late->fresh()->roleIn(Project::where('id', '!=', $project->id)->latest('id')->first()));
    }

    public function test_link_routes_guests_to_register_and_cancel_works(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $ctx = $this->inviteViaApi($owner);
        $url = null;
        Notification::assertSentTo(new AnonymousNotifiable, ProjectInvitationNotification::class, function ($n) use (&$url) {
            $url = $n->url;

            return true;
        });

        $this->app['auth']->forgetGuards();
        $this->get($url)->assertRedirect(route('register', ['email' => 'nueva@ejemplo.com']));
        $this->get(route('register', ['email' => 'nueva@ejemplo.com']))->assertOk()->assertSee('value="nueva@ejemplo.com"', false);
        $this->get('/invitations/token-falso')->assertRedirect(route('login'));

        // Con sesión de otra cuenta: avisa de que es para otro correo
        $other = User::factory()->create();
        $this->actingAs($other)->get($url)->assertRedirect(route('home'))->assertSessionHas('error');

        // Cancelar desde la web
        $invitation = ProjectInvitation::firstOrFail();
        $project = Project::find($ctx['project']);
        $this->actingAs($owner)->get(route('members.index', $project))->assertOk()->assertSee('Invitaciones pendientes')->assertSee('nueva@ejemplo.com');
        $this->actingAs($owner)->delete(route('invitations.destroy', [$project, $invitation]))->assertRedirect();
        $this->assertSame(0, ProjectInvitation::count());
        $this->actingAs($owner)->get($url)->assertRedirect(route('home'))->assertSessionHas('error');
    }

    public function test_only_the_owner_can_invite_and_existing_accounts_are_added_directly(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $mate = User::factory()->create();
        $ctx = $this->buildProject($owner, [[$mate, 'collaborator']]);

        $existing = User::factory()->create(['email' => 'ya@ejemplo.com']);
        $this->postJson("/api/projects/{$ctx['project']}/members", ['email' => 'YA@ejemplo.com', 'role' => 'observer'])->assertCreated();
        $this->assertSame(0, ProjectInvitation::count());

        $this->actingAsUser($mate);
        $this->postJson("/api/projects/{$ctx['project']}/members", ['email' => 'x@ejemplo.com', 'role' => 'observer'])->assertForbidden();
        $this->getJson("/api/projects/{$ctx['project']}/invitations")->assertForbidden();
        $this->assertNotNull($existing);
    }
}
