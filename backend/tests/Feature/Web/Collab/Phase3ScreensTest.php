<?php

namespace Tests\Feature\Web\Collab;

use App\Models\User;
use App\Services\IssueService;
use App\Services\MembershipService;
use App\Services\RepositoryService;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase 3 de la auditoría: listas paginadas y conversaciones largas. */
class Phase3ScreensTest extends TestCase
{
    use BuildsCollabFixtures, RefreshDatabase;

    public function test_long_issue_conversations_load_the_latest_comments_first(): void
    {
        $this->buildCollabProject();
        $issue = $this->makeIssue($this->dev);
        $issues = app(IssueService::class);
        for ($i = 1; $i <= 25; $i++) {
            $this->travel(1)->minutes();
            $issues->comment($issue, $this->dev->fresh(), "Comentario número {$i}.");
        }

        $html = $this->actAs($this->owner)->get(route('issues.show', [$this->repo, $issue->number]))->assertOk()
            ->assertSee('25 comentarios')
            ->assertSee('Mostrando 20 de 25 comentarios')
            ->assertSee('Comentario número 25.')->assertSee('Comentario número 6.')
            ->assertDontSee('Comentario número 5.')
            ->getContent();

        preg_match('/antes=(\d+)#conversacion/', $html, $m);
        $this->get(route('issues.show', [$this->repo, $issue->number, 'antes' => $m[1]]))->assertOk()
            ->assertSee('Comentario número 1.')->assertSee('Comentario número 5.')->assertDontSee('Comentario número 6.')
            ->assertSee('Ir a los más recientes');
    }

    public function test_pull_request_review_and_comment_fields_are_separate_and_pr_can_be_edited(): void
    {
        $this->buildCollabProject();
        $pr = $this->makePullRequest($this->makeBranch('feature/login'));

        $this->actAs($this->owner)->get(route('pulls.show', [$this->repo, $pr->number]))->assertOk()
            ->assertSee('name="review_body"', false)->assertSee('Editar título, descripción y revisores');

        // El autor (colaborador) edita su PR y asigna revisora a la propietaria
        $this->actAs($this->dev)->put(route('pulls.update', [$this->repo, $pr->number]), [
            'title' => 'Login con bloqueo', 'description' => 'Ahora bloquea tras 5 intentos.', 'reviewer_ids' => [$this->owner->id],
        ])->assertRedirect(route('pulls.show', [$this->repo, $pr->number]));
        $pr->refresh();
        $this->assertSame('Login con bloqueo', $pr->title);
        $this->assertSame([$this->owner->id], $pr->reviewers()->pluck('users.id')->all());

        // Un observador no puede editar
        $this->actAs($this->observer)->put(route('pulls.update', [$this->repo, $pr->number]), ['title' => 'X'])->assertForbidden();
    }

    public function test_branches_members_and_project_repositories_are_paginated(): void
    {
        $this->buildCollabProject();
        $repos = app(RepositoryService::class);
        for ($i = 1; $i <= 22; $i++) {
            $repos->createBranch($this->repo, $this->dev->fresh(), sprintf('feature/rama-%02d', $i), $this->main->id);
        }
        $this->actAs($this->owner)->get(route('branches.index', $this->repo))->assertOk()
            ->assertSee('23 ramas')->assertSee('Página 1 de 2');
        $this->get(route('branches.index', [$this->repo, 'page' => 2]))->assertOk()->assertSee('Página 2 de 2');

        $members = app(MembershipService::class);
        // Nombres fijos: con nombres aleatorios alguno podía llamarse «Carlos» y romper la búsqueda
        foreach (User::factory()->count(26)->sequence(fn ($seq) => ['name' => 'Usuario '.($seq->index + 1)])->create() as $user) {
            $members->add($this->project, $this->owner->fresh(), $user->email, Roles::OBSERVER);
        }
        $this->get(route('members.index', $this->project))->assertOk()->assertSee('29 miembros')->assertSee('Página 1 de 2');
        $this->get(route('members.index', [$this->project, 'q' => 'Carlos']))->assertOk()
            ->assertSee('1 miembro para «Carlos»')->assertSee('Carlos Colaborador')->assertDontSee('Olga Observadora');

        for ($i = 1; $i <= 12; $i++) {
            $repos->create($this->project, $this->owner->fresh(), ['name' => sprintf('repo-%02d', $i)]);
        }
        $this->get(route('projects.show', $this->project))->assertOk()
            ->assertSee('Repositorios (13)')->assertSee('Página 1 de 2');
    }

    public function test_project_list_shows_open_issues_and_pending_pull_requests(): void
    {
        $this->buildCollabProject();
        $this->makeIssue($this->dev);
        $this->makePullRequest($this->makeBranch('feature/login'));

        $this->actAs($this->owner)->get(route('projects.index'))->assertOk()
            ->assertSee('1 issue abierta')->assertSee('1 PR pendiente');
    }
}
