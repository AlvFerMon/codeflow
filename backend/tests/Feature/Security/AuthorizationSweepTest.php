<?php

namespace Tests\Feature\Security;

use App\Models\Commit;
use App\Models\PullRequest;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsProjects;
use Tests\TestCase;

/**
 * Recorre TODAS las rutas que llevan un proyecto, repositorio, rama, commit, issue o PR y comprueba que un usuario
 * ajeno no consigue nada (ni leer en privado ni escribir en público). Si se añade una ruta sin autorización, falla.
 */
class AuthorizationSweepTest extends TestCase
{
    use BuildsProjects, RefreshDatabase;

    private const SCOPED = '/\{(project|repository|branch|commit|issue|pullRequest|number|sha)\}/';

    public function test_outsider_cannot_touch_private_resources(): void
    {
        $this->assertSweep(public: false);
    }

    public function test_outsider_can_only_read_public_resources(): void
    {
        $this->assertSweep(public: true);
    }

    private function assertSweep(bool $public): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $ctx = $this->buildProject($owner);
        [$feature, $prId] = $this->openPullRequest($ctx);
        $issue = $this->postJson("/api/repositories/{$ctx['repo']}/issues", ['title' => 'Fallo'])->assertCreated()->json('id');
        if ($public) {
            $this->makePublic($ctx['repo']);
        }
        $commit = Commit::where('repository_id', $ctx['repo'])->latest('id')->firstOrFail();
        $params = [
            'project' => $ctx['project'], 'repository' => $ctx['repo'], 'branch' => $feature, 'commit' => $commit->id,
            'issue' => $issue, 'pullRequest' => $prId, 'number' => PullRequest::find($prId)->number, 'sha' => $commit->sha,
            'path' => 'README.md', 'user' => $owner->id, 'id' => 1,
        ];

        $leaks = [];
        foreach (Route::getRoutes() as $route) {
            if (! preg_match(self::SCOPED, $route->uri())) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                if ($public && $method === 'GET') {
                    continue; // en público se puede leer
                }
                $uri = '/'.preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $params[$m[1]] ?? 'x', $route->uri());
                $this->app['auth']->forgetGuards();
                if (str_starts_with($uri, '/api/')) {
                    Sanctum::actingAs($outsider->fresh());
                    $status = $this->json($method, $uri)->getStatusCode();
                } else {
                    $this->actingAs($outsider->fresh());
                    $status = $this->withoutMiddleware(ValidateCsrfToken::class)->call($method, $uri)->getStatusCode();
                }
                if ($status < 400) {
                    $leaks[] = "$status $method {$route->uri()}";
                }
            }
        }

        $this->assertSame([], $leaks, "Rutas accesibles para un usuario ajeno:\n".implode("\n", $leaks));
    }
}
