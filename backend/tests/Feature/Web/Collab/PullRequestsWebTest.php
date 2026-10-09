<?php

namespace Tests\Feature\Web\Collab;

use App\Models\PullRequest;
use App\Services\PullRequestService;
use App\Services\RepositoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PullRequestsWebTest extends TestCase
{
    use BuildsCollabFixtures, RefreshDatabase;

    public function test_guest_sees_pull_requests_of_a_public_repository_read_only(): void
    {
        $this->buildCollabProject('public');
        $pr = $this->makePullRequest($this->makeBranch('feature/login'));

        $this->asGuest()->get(route('pulls.index', $this->repo))
            ->assertOk()->assertSee('Login de usuarios')->assertSee('feature/login')->assertDontSee('Nuevo pull request');

        $this->get(route('pulls.show', [$this->repo, $pr->number]))
            ->assertOk()
            ->assertSee('quiere fusionar')
            ->assertDontSee('Fusionar pull request')
            ->assertDontSee('Revisar cambios')
            ->assertDontSee('Añadir un comentario');

        $this->get(route('pulls.show', [$this->repo, $pr->number, 'tab' => 'files']))
            ->assertOk()->assertSee('src/Login.php');
    }

    public function test_private_repository_pull_requests_are_404_for_guests_and_outsiders(): void
    {
        $this->buildCollabProject();
        $pr = $this->makePullRequest($this->makeBranch('feature/login'));

        $this->asGuest()->get(route('pulls.index', $this->repo))->assertNotFound();
        $this->get(route('pulls.show', [$this->repo, $pr->number]))->assertNotFound();
        $this->actAs($this->outsider)->get(route('pulls.show', [$this->repo, $pr->number]))->assertNotFound();
    }

    public function test_collaborator_opens_a_pull_request_from_the_web_with_diff_preview(): void
    {
        $this->buildCollabProject();
        $this->makeBranch('feature/login');
        $issue = $this->makeIssue($this->dev);

        $this->actAs($this->dev)->get(route('pulls.create', [$this->repo, 'source' => 'feature/login']))
            ->assertOk()
            ->assertSee('src/Login.php')          // vista previa del diff
            ->assertSee('Abrir pull request')
            ->assertSee($this->owner->name)        // revisor posible
            ->assertDontSee($this->observer->name); // los observadores no revisan

        $source = $this->repo->branches()->where('name', 'feature/login')->first();
        $this->post(route('pulls.store', $this->repo), [
            'title' => 'Login de usuarios',
            'description' => 'Añade el login',
            'source_branch_id' => $source->id,
            'target_branch_id' => $this->main->id,
            'reviewer_ids' => [$this->owner->id],
            'issue_ids' => [$issue->id],
        ])->assertRedirect(route('pulls.show', [$this->repo, 1]));

        $pr = PullRequest::where('repository_id', $this->repo->id)->where('number', 1)->firstOrFail();
        $this->assertSame('in_review', $pr->status);
        $this->assertSame([$issue->id], $pr->issues()->pluck('issues.id')->all());

        $this->get(route('pulls.show', [$this->repo, 1]))
            ->assertOk()->assertSee('En revisión')->assertSee('#'.$issue->number)->assertSee('1 commit');
    }

    public function test_create_page_warns_when_there_are_no_differences(): void
    {
        $this->buildCollabProject();
        app(RepositoryService::class)->createBranch($this->repo, $this->dev, 'vacia', $this->main->id);

        $this->actAs($this->dev)->get(route('pulls.create', [$this->repo, 'source' => 'vacia', 'target' => 'main']))
            ->assertOk()->assertSee('No hay diferencias')->assertDontSee('Abrir pull request');
    }

    public function test_duplicate_pull_request_shows_a_flash_error(): void
    {
        $this->buildCollabProject();
        $source = $this->makeBranch('feature/login');
        $this->makePullRequest($source);

        $this->actAs($this->dev)->from(route('pulls.create', $this->repo))->post(route('pulls.store', $this->repo), [
            'title' => 'Otra vez', 'source_branch_id' => $source->id, 'target_branch_id' => $this->main->id,
        ])->assertRedirect(route('pulls.create', $this->repo))
            ->assertSessionHas('error', 'Ya existe un Pull Request abierto entre estas dos ramas.');
    }

    public function test_owner_approves_and_collaborator_merges(): void
    {
        $this->buildCollabProject();
        $issue = $this->makeIssue($this->dev);
        $pr = app(PullRequestService::class)->open($this->repo, $this->dev->fresh(), [
            'title' => 'Login', 'source_branch_id' => $this->makeBranch('feature/login')->id,
            'target_branch_id' => $this->main->id, 'reviewers' => [$this->owner->id], 'issues' => [$issue->id],
        ]);

        // Sin aprobación, el colaborador no puede fusionar
        $this->actAs($this->dev)->get(route('pulls.show', [$this->repo, $pr->number]))
            ->assertOk()->assertDontSee('Fusionar pull request')->assertSee('necesita al menos una aprobación');
        $this->post(route('pulls.merge', [$this->repo, $pr->number]))->assertForbidden();

        $this->actAs($this->owner)->get(route('pulls.show', [$this->repo, $pr->number]))->assertSee('Revisar cambios');
        $this->post(route('pulls.review', [$this->repo, $pr->number]), ['state' => 'approved', 'body' => 'Todo bien'])
            ->assertRedirect()->assertSessionHas('status');
        $this->assertSame('approved', $pr->fresh()->status);

        $this->actAs($this->dev)->get(route('pulls.show', [$this->repo, $pr->number]))->assertSee('Fusionar pull request');
        $this->post(route('pulls.merge', [$this->repo, $pr->number]))->assertRedirect()->assertSessionHas('status');

        $pr->refresh();
        $this->assertSame('merged', $pr->status);
        $this->assertSame('resolved', $issue->fresh()->status);

        $this->get(route('pulls.show', [$this->repo, $pr->number]))->assertOk()->assertSee('Fusionado')->assertSee('fusionó');
        $this->get(route('pulls.show', [$this->repo, $pr->number, 'tab' => 'files']))->assertOk()->assertSee('src/Login.php');
        $this->get(route('pulls.index', [$this->repo, 'state' => 'closed']))->assertOk()->assertSee('Login');
    }

    public function test_changes_requested_requires_a_body(): void
    {
        $this->buildCollabProject();
        $pr = $this->makePullRequest($this->makeBranch('feature/login'));

        $this->actAs($this->owner)->post(route('pulls.review', [$this->repo, $pr->number]), ['state' => 'changes_requested'])
            ->assertSessionHasErrors('review_body');
        $this->assertSame(0, $pr->reviews()->count());
    }

    public function test_author_cannot_review_own_pull_request(): void
    {
        $this->buildCollabProject();
        $pr = $this->makePullRequest($this->makeBranch('feature/login'));

        $this->actAs($this->dev)->get(route('pulls.show', [$this->repo, $pr->number]))->assertOk()->assertDontSee('Revisar cambios');
        $this->post(route('pulls.review', [$this->repo, $pr->number]), ['state' => 'approved'])->assertForbidden();
        $this->assertSame(0, $pr->reviews()->count());
    }

    public function test_observer_can_view_and_comment_but_not_act_on_code(): void
    {
        $this->buildCollabProject();
        $pr = $this->makePullRequest($this->makeBranch('feature/login'));

        $this->actAs($this->observer)->get(route('pulls.index', $this->repo))->assertOk()->assertDontSee('Nuevo pull request');
        $this->get(route('pulls.show', [$this->repo, $pr->number]))
            ->assertOk()->assertSee('Añadir un comentario')->assertDontSee('Comentar sobre una línea')
            ->assertDontSee('Revisar cambios')->assertDontSee('Fusionar pull request');

        $this->post(route('pulls.comment', [$this->repo, $pr->number]), ['body' => '¿Incluye el bloqueo?'])->assertRedirect();
        $this->get(route('pulls.show', [$this->repo, $pr->number]))->assertSee('Observador')->assertSee('¿Incluye el bloqueo?');

        $alert = $this->owner->fresh()->notifications->firstWhere('data.event', 'pull_request.commented');
        $this->assertSame('alert', $alert?->data['kind']);

        $this->post(route('pulls.comment', [$this->repo, $pr->number]), ['body' => 'Aquí', 'path' => 'src/Login.php', 'line' => 2])->assertForbidden();
        $this->post(route('pulls.review', [$this->repo, $pr->number]), ['state' => 'approved'])->assertForbidden();
        $this->post(route('pulls.merge', [$this->repo, $pr->number]))->assertForbidden();
        $this->post(route('pulls.close', [$this->repo, $pr->number]))->assertForbidden();
        $this->get(route('pulls.create', $this->repo))->assertForbidden();
        $this->assertSame(1, $pr->comments()->count());
    }

    public function test_collaborator_can_comment_on_a_line_of_code(): void
    {
        $this->buildCollabProject();
        $pr = $this->makePullRequest($this->makeBranch('feature/login'));

        $this->actAs($this->owner)->post(route('pulls.comment', [$this->repo, $pr->number]), [
            'body' => 'Falta validar', 'path' => 'src/Login.php', 'line' => 2,
        ])->assertRedirect();

        $this->get(route('pulls.show', [$this->repo, $pr->number]))->assertSee('src/Login.php')->assertSee('línea 2');
    }

    public function test_merge_conflict_lists_conflicts_without_server_error(): void
    {
        $this->buildCollabProject();
        $feature = $this->makeBranch('feature/readme', 'README.md', "# app\n\nVersión de la rama\n");
        app(RepositoryService::class)->commitChanges($this->main->fresh(), $this->owner->fresh(), 'README en main', [
            ['path' => 'README.md', 'content' => "# app\n\nVersión de main\n"],
        ]);
        $pr = $this->makePullRequest($feature);

        $this->actAs($this->owner)->get(route('pulls.show', [$this->repo, $pr->number]))
            ->assertOk()->assertSee('Hay conflictos')->assertSee('README.md')->assertDontSee('Fusionar pull request');

        $this->from(route('pulls.show', [$this->repo, $pr->number]))
            ->post(route('pulls.merge', [$this->repo, $pr->number]))
            ->assertRedirect(route('pulls.show', [$this->repo, $pr->number]))
            ->assertSessionHas('error');
        $this->assertStringContainsString('README.md', session('error'));
        $this->assertNotSame('merged', $pr->fresh()->status);
    }

    public function test_author_closes_and_reopens(): void
    {
        $this->buildCollabProject();
        $pr = $this->makePullRequest($this->makeBranch('feature/login'));

        $this->actAs($this->dev)->post(route('pulls.close', [$this->repo, $pr->number]))->assertRedirect();
        $this->assertSame('closed', $pr->fresh()->status);
        $this->get(route('pulls.show', [$this->repo, $pr->number]))->assertSee('se cerró sin fusionar')->assertSee('Reabrir');

        $this->post(route('pulls.reopen', [$this->repo, $pr->number]))->assertRedirect();
        $this->assertSame('open', $pr->fresh()->status);

        // Reabrir algo que ya está abierto: error de negocio como mensaje flash, no 500
        $this->post(route('pulls.reopen', [$this->repo, $pr->number]))->assertRedirect()->assertSessionHas('error');
    }

    public function test_list_filters_by_status_and_author(): void
    {
        $this->buildCollabProject();
        $this->makePullRequest($this->makeBranch('feature/a'));
        $other = $this->makePullRequest($this->makeBranch('feature/b'), $this->owner);
        $other->update(['title' => 'Otro cambio']);

        $this->actAs($this->observer)->get(route('pulls.index', [$this->repo, 'author' => $this->owner->id]))
            ->assertOk()->assertSee('Otro cambio')->assertDontSee('feature/a');
        $this->get(route('pulls.index', [$this->repo, 'status' => 'merged']))
            ->assertOk()->assertSee('Ningún pull request coincide');
    }
}
