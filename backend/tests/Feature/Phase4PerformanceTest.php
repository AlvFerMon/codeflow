<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Services\IssueService;
use App\Services\VcsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Web\Account\CreatesProjects;
use Tests\TestCase;

/** Fase 4 de la auditoría: memoria por petición, caché de diffs y API paginada. */
class Phase4PerformanceTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    public function test_graph_is_memoized_per_request_and_refreshed_after_a_commit(): void
    {
        $this->setUpTeam();
        $repo = $this->makeRepository($this->project, $this->owner, 'web');
        $vcs = app(VcsService::class);

        $first = $vcs->graph($repo->id);
        $this->assertSame($first, $vcs->graph($repo->id));

        $branch = Branch::where('repository_id', $repo->id)->where('name', 'main')->firstOrFail();
        $vcs->commit($branch, $this->owner, 'Otro', [['path' => 'a.txt', 'content' => "a\n"]]);
        $this->assertCount($first->count() + 1, $vcs->graph($repo->id));
    }

    public function test_diff_summary_is_cached_but_never_the_code(): void
    {
        $this->setUpTeam();
        $repo = $this->makeRepository($this->project, $this->owner, 'web');
        $branch = Branch::where('repository_id', $repo->id)->where('name', 'main')->firstOrFail();
        $vcs = app(VcsService::class);
        $commit = $vcs->commit($branch, $this->owner, 'Cambio', [['path' => 'secreto.txt', 'content' => "clave muy secreta\n"]]);

        $summary = $vcs->diff($commit->parent_id, $commit->id);
        $cached = Cache::get('vcs:diff:'.$commit->parent_id.':'.$commit->id);
        $this->assertSame($summary, $cached);
        $this->assertStringNotContainsString('clave muy secreta', serialize($cached));

        // La segunda vez no se leen blobs
        DB::enableQueryLog();
        $vcs->diff($commit->parent_id, $commit->id);
        $this->assertEmpty(collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'blobs')));
    }

    public function test_api_lists_are_paginated_and_details_carry_only_recent_comments(): void
    {
        $this->setUpTeam();
        $repo = $this->makeRepository($this->project, $this->owner, 'web');
        $issue = app(IssueService::class)->create($repo, $this->owner, ['title' => 'Larga']);
        for ($i = 1; $i <= 35; $i++) {
            app(IssueService::class)->comment($issue, $this->owner, "Comentario {$i}");
        }
        Sanctum::actingAs($this->owner->fresh());

        $this->getJson("/api/issues/{$issue->id}")->assertOk()
            ->assertJsonPath('comments_count', 35)->assertJsonCount(20, 'comments');
        $this->getJson("/api/issues/{$issue->id}/comments")->assertOk()
            ->assertJsonPath('total', 35)->assertJsonCount(30, 'data')->assertJsonPath('data.0.body', 'Comentario 1');
        $this->getJson("/api/issues/{$issue->id}/comments?page=2")->assertOk()->assertJsonCount(5, 'data');

        $this->getJson("/api/projects/{$this->project->id}/members")->assertOk()->assertJsonPath('total', 3);
        $this->getJson("/api/projects/{$this->project->id}/repositories")->assertOk()
            ->assertJsonPath('data.0.name', 'web')->assertJsonMissingPath('data.0.encrypted_key');
        $this->getJson("/api/repositories/{$repo->id}/branches")->assertOk()->assertJsonPath('data.0.name', 'main');
    }
}
