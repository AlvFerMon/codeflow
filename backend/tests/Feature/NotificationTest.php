<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    private User $owner;

    private User $dev;

    private User $observer;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Ana']);
        $this->dev = User::factory()->create(['name' => 'María']);
        $this->observer = User::factory()->create(['name' => 'Óscar']);

        $this->ctx = $this->buildProject($this->owner, [[$this->dev, 'collaborator'], [$this->observer, 'observer']]);
    }

    /** Notificaciones del usuario actual con un evento concreto (el orden dentro del mismo segundo no está garantizado). */
    private function notificationsFor(string $event): array
    {
        return collect($this->getJson('/api/me/notifications')->assertOk()->json('data'))->where('event', $event)->values()->all();
    }

    public function test_added_member_is_notified(): void
    {
        $this->actingAsUser($this->observer);

        [$notification] = $this->notificationsFor('member.added');
        $this->assertSame('change', $notification['kind']);
        $this->assertSame('Ana te ha dado acceso al proyecto Proyecto DAM como observador', $notification['message']);
        $this->assertSame(['id' => $this->owner->id, 'name' => 'Ana'], $notification['actor']);
        $this->assertSame(['id' => $this->ctx['project'], 'name' => 'Proyecto DAM'], $notification['project']);
        $this->assertSame("/projects/{$this->ctx['project']}", $notification['url']);
        $this->assertNull($notification['read_at']);

        // Al cambiar el rol, solo se avisa al afectado
        $this->actingAsUser($this->owner);
        $this->putJson("/api/projects/{$this->ctx['project']}/members/{$this->observer->id}", ['role' => 'collaborator'])->assertOk();

        $this->actingAsUser($this->observer);
        $this->assertSame('Ana ha cambiado tu rol en el proyecto Proyecto DAM a colaborador', $this->notificationsFor('member.role_changed')[0]['message']);
        $this->actingAsUser($this->dev);
        $this->assertCount(0, $this->notificationsFor('member.role_changed'));
    }

    public function test_members_are_notified_but_not_the_actor(): void
    {
        $this->actingAsUser($this->dev);
        $issue = $this->postJson("/api/repositories/{$this->ctx['repo']}/issues", ['title' => 'Login roto'])->assertCreated()->json();

        foreach ([$this->owner, $this->observer] as $user) {
            $this->actingAsUser($user);
            $notifications = $this->notificationsFor('issue.created');
            $this->assertCount(1, $notifications);
            $this->assertSame('María creó la issue #1 «Login roto» en el repositorio app', $notifications[0]['message']);
            $this->assertSame("/repositories/{$this->ctx['repo']}/issues/{$issue['number']}", $notifications[0]['url']);
            $this->assertSame(['type' => 'issue', 'id' => $issue['id']], $notifications[0]['subject']);
            $this->assertSame($this->ctx['repo'], $notifications[0]['repository_id']);
        }

        $this->actingAsUser($this->dev);
        $events = collect($this->getJson('/api/me/notifications')->json('data'))->pluck('event');
        $this->assertNotContains('issue.created', $events);
    }

    public function test_commit_notification_does_not_include_message_in_activity(): void
    {
        $this->actingAsUser($this->dev);
        $this->postJson("/api/branches/{$this->ctx['main']}/commits", [
            'message' => 'Secreto', 'changes' => [['path' => 'a.txt', 'content' => 'a']],
        ])->assertCreated();

        $this->actingAsUser($this->owner);
        [$notification] = $this->notificationsFor('commit.created');
        $this->assertSame('Nuevos cambios', $notification['title']);
        $this->assertStringStartsWith('María subió cambios a la rama main en el repositorio app', $notification['message']);
        $this->assertStringNotContainsString('Secreto', $notification['message']);

        $activity = $this->getJson("/api/projects/{$this->ctx['project']}/activity?action=commit")->json('data.0.properties');
        $this->assertEqualsCanonicalizing(['branch', 'sha'], array_keys($activity));
    }

    public function test_observer_comments_are_alerts_for_other_members(): void
    {
        $this->actingAsUser($this->dev);
        $issue = $this->postJson("/api/repositories/{$this->ctx['repo']}/issues", ['title' => 'Bug'])->json('id');
        [, $pr] = $this->openPullRequest($this->ctx);

        $this->actingAsUser($this->observer);
        $this->postJson("/api/issues/$issue/comments", ['body' => 'Ojo con esto'])->assertCreated();
        $this->postJson("/api/pull-requests/$pr/comments", ['body' => 'Y con esto'])->assertCreated();

        foreach ([$this->owner, $this->dev] as $user) {
            $this->actingAsUser($user);
            $alerts = collect($this->getJson('/api/me/notifications')->json('data'))->where('kind', 'alert')->keyBy('event');
            $this->assertCount(2, $alerts);
            $this->assertSame('Óscar comentó en la issue #1 «Bug»', $alerts['issue.commented']['message']);
            $this->assertSame('Comentario de un observador', $alerts['issue.commented']['title']);
            $this->assertStringStartsWith('Óscar comentó en el Pull Request #1', $alerts['pull_request.commented']['message']);
        }

        // Los comentarios de un colaborador son cambios normales
        $this->actingAsUser($this->dev);
        $this->postJson("/api/issues/$issue/comments", ['body' => 'Lo miro'])->assertCreated();
        $this->actingAsUser($this->owner);
        $kinds = collect($this->notificationsFor('issue.commented'))->pluck('kind')->sort()->values()->all();
        $this->assertSame(['alert', 'change'], $kinds);

        // El observador no recibe su propia alerta
        $this->actingAsUser($this->observer);
        $this->assertCount(0, collect($this->getJson('/api/me/notifications')->json('data'))->where('kind', 'alert'));
    }

    public function test_pull_request_lifecycle_notifications(): void
    {
        $this->actingAsUser($this->dev);
        [, $pr] = $this->openPullRequest($this->ctx);

        $this->actingAsUser($this->owner);
        $this->postJson("/api/pull-requests/$pr/reviews", ['state' => 'approved'])->assertCreated();
        $this->postJson("/api/pull-requests/$pr/merge")->assertOk();

        $this->actingAsUser($this->dev);
        $this->assertSame('Ana aprobó el Pull Request #1 «PR feature/x»', $this->notificationsFor('pull_request.approved')[0]['message']);
        $this->assertSame('Ana fusionó el Pull Request #1 «PR feature/x» en main', $this->notificationsFor('pull_request.merged')[0]['message']);

        $this->actingAsUser($this->observer);
        $this->assertCount(1, $this->notificationsFor('pull_request.opened'));
        $this->assertCount(1, $this->notificationsFor('branch.created'));
    }

    public function test_unread_count_and_mark_as_read(): void
    {
        $this->actingAsUser($this->dev);
        $this->postJson("/api/repositories/{$this->ctx['repo']}/issues", ['title' => 'Uno'])->assertCreated();
        $this->postJson("/api/repositories/{$this->ctx['repo']}/issues", ['title' => 'Dos'])->assertCreated();

        // Observador: acceso al proyecto + repositorio creado + 2 issues
        $this->actingAsUser($this->observer);
        $this->getJson('/api/me/notifications/unread-count')->assertOk()->assertJsonPath('count', 4);

        $id = $this->getJson('/api/me/notifications')->json('data.0.id');
        $this->postJson("/api/me/notifications/$id/read")->assertOk()->assertJsonPath('id', $id);
        $this->getJson('/api/me/notifications/unread-count')->assertJsonPath('count', 3);
        $this->getJson('/api/me/notifications?unread=1')->assertJsonCount(3, 'data');

        $this->postJson('/api/me/notifications/read-all')->assertOk()->assertJsonPath('updated', 3);
        $this->getJson('/api/me/notifications/unread-count')->assertJsonPath('count', 0);
        $this->getJson('/api/me/notifications')->assertJsonCount(4, 'data');

        // No se pueden tocar notificaciones de otro usuario
        $this->actingAsUser($this->owner);
        $this->postJson("/api/me/notifications/$id/read")->assertNotFound();
    }
}
