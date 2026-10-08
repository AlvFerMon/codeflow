<?php

namespace Tests\Feature\Web\Account;

use App\Models\User;
use App\Notifications\ProjectActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function notify(array $overrides = []): string
    {
        $this->user->notify(new ProjectActivityNotification(array_merge([
            'kind' => 'change',
            'event' => 'issue.created',
            'title' => 'Nueva issue',
            'message' => 'Ana abrió la issue #1 «Fallo»',
            'project_id' => 1,
            'project_name' => 'Proyecto DAM',
            'repository_id' => 3,
            'subject_type' => 'issue',
            'subject_id' => 1,
            'url' => '/repositories/3/issues/1',
            'actor' => ['id' => 9, 'name' => 'Ana'],
        ], $overrides)));

        return $this->user->notifications()->latest()->first()->id;
    }

    public function test_lists_notifications_with_alert_styling(): void
    {
        $this->notify();
        $this->notify(['kind' => 'alert', 'title' => 'Comentario de un observador', 'message' => 'Olga comentó en la issue #1']);

        $this->actingAs($this->user)->get('/notifications')->assertOk()
            ->assertSee('Ana abrió la issue #1 «Fallo»')
            ->assertSee('Comentario de un observador')
            ->assertSee('Alerta')
            ->assertSee('Proyecto: Proyecto DAM')
            ->assertSee('Por Ana')
            ->assertSee('Marcar todas como leídas');
    }

    public function test_alert_badge_absent_for_regular_notifications(): void
    {
        $this->notify();

        $this->actingAs($this->user)->get('/notifications')->assertOk()->assertDontSee('>Alerta<', false);
    }

    public function test_unread_filter(): void
    {
        $read = $this->notify(['message' => 'Mensaje ya leído']);
        $this->user->notifications()->whereKey($read)->update(['read_at' => now()]);
        $this->notify(['message' => 'Mensaje pendiente']);

        $this->actingAs($this->user)->get('/notifications?filter=unread')->assertOk()
            ->assertSee('Mensaje pendiente')->assertDontSee('Mensaje ya leído');
        $this->get('/notifications')->assertSee('Mensaje pendiente')->assertSee('Mensaje ya leído');
    }

    public function test_reading_marks_as_read_and_redirects_to_target(): void
    {
        $id = $this->notify();

        $this->actingAs($this->user)->post(route('notifications.read', $id))->assertRedirect('/repositories/3/issues/1');
        $this->assertNotNull($this->user->notifications()->find($id)->read_at);
    }

    public function test_unsafe_urls_redirect_to_inbox(): void
    {
        foreach (['https://evil.test/x', '//evil.test', '/\\evil.test', 'javascript:alert(1)', null] as $url) {
            $id = $this->notify(['url' => $url]);
            $this->actingAs($this->user)->post(route('notifications.read', $id))->assertRedirect(route('notifications.index'));
        }
    }

    public function test_cannot_read_someone_elses_notification(): void
    {
        $id = $this->notify();

        $this->actingAs(User::factory()->create())->post(route('notifications.read', $id))->assertNotFound();
        $this->assertNull($this->user->notifications()->find($id)->read_at);
    }

    public function test_read_all(): void
    {
        $this->notify();
        $this->notify();

        $this->actingAs($this->user)->from('/notifications')->post(route('notifications.readAll'))
            ->assertRedirect('/notifications')->assertSessionHas('status');
        $this->assertSame(0, $this->user->unreadNotifications()->count());
    }

    public function test_empty_state(): void
    {
        $this->actingAs($this->user)->get('/notifications')->assertOk()->assertSee('No tienes notificaciones');
    }
}
