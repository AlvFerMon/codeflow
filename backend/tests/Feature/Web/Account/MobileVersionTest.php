<?php

namespace Tests\Feature\Web\Account;

use App\Http\Controllers\Web\Mobile\MobileController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Versión móvil de la web (/m): solo consulta de actividad, avisos, proyectos y cuenta. */
class MobileVersionTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    private const ANDROID = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Mobile Safari/537.36';

    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36';

    public function test_phones_open_the_mobile_version_and_computers_the_full_one(): void
    {
        $this->setUpTeam();
        $this->as($this->owner);

        $this->withHeader('User-Agent', self::IPHONE)->get('/')->assertRedirect(route('mobile.activity'));
        $this->withHeader('User-Agent', self::ANDROID)->get('/')->assertRedirect(route('mobile.activity'));
        $this->withHeader('User-Agent', self::DESKTOP)->get('/')->assertOk();

        // Elegir la versión completa en el móvil se recuerda con una cookie
        $this->withHeader('User-Agent', self::IPHONE)->get(route('mobile.full'))
            ->assertCookie(MobileController::FULL_VERSION_COOKIE);
        $this->withHeader('User-Agent', self::IPHONE)->withCookie(MobileController::FULL_VERSION_COOKIE, '1')
            ->get('/')->assertOk();
    }

    public function test_guests_go_to_login(): void
    {
        $this->get(route('mobile.activity'))->assertRedirect(route('login'));
    }

    public function test_activity_shows_who_what_where_and_when_without_links_to_code(): void
    {
        $this->setUpTeam();
        $repo = $this->makeRepository($this->project, $this->owner, 'api');
        $this->makeProject($this->outsider, 'Proyecto ajeno');

        $response = $this->as($this->collaborator)->get(route('mobile.activity'))->assertOk()
            ->assertSee('Laura Propietaria')
            ->assertSee('Proyecto DAM')
            ->assertDontSee('Proyecto ajeno')
            ->assertSee('aria-current="page"', false);

        // Solo consulta: ningún enlace a repositorios, código, subidas o pull requests
        $response->assertDontSee(route('repositories.show', $repo), false)
            ->assertDontSee('/upload', false);

        $this->get(route('mobile.activity', ['project' => $this->project->id]))->assertOk()->assertSee('Proyecto DAM');
    }

    public function test_notifications_are_marked_as_read_without_leaving_the_mobile_version(): void
    {
        $this->setUpTeam();
        $notification = $this->collaborator->notifications()->first();
        $this->assertNotNull($notification, 'Al añadir al colaborador se le notifica');

        $this->as($this->collaborator)->get(route('mobile.notifications'))->assertOk()->assertSee($notification->data['title']);

        $this->from(route('mobile.notifications'))->post(route('mobile.notifications.read', $notification->id))
            ->assertRedirect(route('mobile.notifications'));
        $this->assertNotNull($notification->fresh()->read_at);

        $this->post(route('mobile.notifications.readAll'))->assertRedirect();
        $this->assertSame(0, $this->collaborator->fresh()->unreadNotifications()->count());
    }

    public function test_projects_and_account(): void
    {
        $this->setUpTeam();
        $this->makeProject($this->outsider, 'Proyecto ajeno');

        $this->as($this->observer)->get(route('mobile.projects'))->assertOk()
            ->assertSee('Proyecto DAM')->assertDontSee('Proyecto ajeno')
            ->assertSee(route('mobile.activity', ['project' => $this->project->id]), false);

        $this->get(route('mobile.account'))->assertOk()
            ->assertSee($this->observer->email)
            ->assertSee('Cerrar sesión')
            ->assertSee(route('mobile.full'), false);
    }
}
