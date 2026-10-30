<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    public function test_pages_send_security_headers(): void
    {
        $response = $this->get('/login')->assertOk();

        $response->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    public function test_api_404_does_not_reveal_model_names(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/repositories/999999')->assertNotFound()->assertExactJson(['message' => 'No encontrado.']);
    }

    /** Ninguna página GET devuelve un 500 si alguien mete listas en la URL (?q[]=x). */
    public function test_array_query_parameters_never_cause_server_errors(): void
    {
        $owner = User::factory()->create();
        $ctx = $this->buildProject($owner);
        [$feature, $prId] = $this->openPullRequest($ctx);
        $params = ['project' => $ctx['project'], 'repository' => $ctx['repo'], 'branch' => $feature, 'pullRequest' => $prId, 'number' => 1, 'path' => 'README.md', 'user' => $owner->id];

        $errors = [];
        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            $uri = '/'.preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $params[$m[1]] ?? '1', $route->uri()).'?q[]=x&status[]=x&rama[]=x&action[]=x';
            $this->app['auth']->forgetGuards();
            if (str_starts_with($uri, '/api/')) {
                Sanctum::actingAs($owner->fresh());
                $status = $this->getJson($uri)->getStatusCode();
            } else {
                $this->actingAs($owner->fresh());
                $status = $this->get($uri)->getStatusCode();
            }
            if ($status >= 500) {
                $errors[] = "$status {$route->uri()}";
            }
        }

        $this->assertSame([], $errors);
        $this->getJson('/api/explore/repositories?q[]=x')->assertStatus(400);
    }

    /** Ids no numéricos en la URL (/projects/abc) dan 404, no un error de PostgreSQL. */
    public function test_garbage_route_parameters_never_cause_server_errors(): void
    {
        $user = User::factory()->create();
        $errors = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_contains($route->uri(), '{') || str_contains($route->uri(), 'cron')) {
                continue;
            }
            $uri = '/'.preg_replace('/\{\w+\??\}/', 'abc', $route->uri());
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $this->app['auth']->forgetGuards();
                if (str_starts_with($uri, '/api/')) {
                    Sanctum::actingAs($user->fresh());
                    $status = $this->json($method, $uri)->getStatusCode();
                } else {
                    $this->actingAs($user->fresh());
                    $status = $this->withoutMiddleware(ValidateCsrfToken::class)->call($method, $uri)->getStatusCode();
                }
                if ($status >= 500) {
                    $errors[] = "$status $method {$route->uri()}";
                }
            }
        }

        $this->assertSame([], $errors);
    }
}
