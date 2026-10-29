<?php

namespace Tests\Feature\Web\Collab;

use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssuesWebTest extends TestCase
{
    use BuildsCollabFixtures, RefreshDatabase;

    public function test_guest_sees_issues_of_a_public_repository_read_only(): void
    {
        $this->buildCollabProject('public');
        $issue = $this->makeIssue($this->dev, ['labels' => ['bug'], 'description' => 'Texto **importante**']);

        $this->asGuest()->get(route('issues.index', $this->repo))
            ->assertOk()->assertSee('Error en el login')->assertSee('bug')->assertDontSee('Nueva issue');

        $this->get(route('issues.show', [$this->repo, $issue->number]))
            ->assertOk()
            ->assertSee('<strong>importante</strong>', false)
            ->assertDontSee('Añadir un comentario')
            ->assertSee('Inicia sesión');
    }

    public function test_private_repository_issues_are_404_for_guests_and_outsiders(): void
    {
        $this->buildCollabProject();
        $issue = $this->makeIssue($this->dev);

        $this->asGuest()->get(route('issues.index', $this->repo))->assertNotFound();
        $this->get(route('issues.show', [$this->repo, $issue->number]))->assertNotFound();

        $this->actAs($this->outsider)->get(route('issues.index', $this->repo))->assertNotFound();
        $this->get(route('issues.show', [$this->repo, $issue->number]))->assertNotFound();
    }

    public function test_unknown_issue_number_is_404(): void
    {
        $this->buildCollabProject();

        $this->actAs($this->dev)->get(route('issues.show', [$this->repo, 99]))->assertNotFound();
    }

    public function test_user_html_in_markdown_is_escaped(): void
    {
        $this->buildCollabProject('public');
        $issue = $this->makeIssue($this->dev, ['description' => '<script>alert(1)</script> [x](javascript:alert(1))']);

        $this->asGuest()->get(route('issues.show', [$this->repo, $issue->number]))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href="javascript:', false);
    }

    public function test_observer_can_view_and_comment_but_cannot_create_or_update(): void
    {
        $this->buildCollabProject();
        $issue = $this->makeIssue($this->dev);

        $this->actAs($this->observer)->get(route('issues.index', $this->repo))->assertOk()->assertDontSee('Nueva issue');
        $this->get(route('issues.show', [$this->repo, $issue->number]))
            ->assertOk()->assertSee('Añadir un comentario')->assertDontSee('Eliminar issue');

        $this->post(route('issues.comment', [$this->repo, $issue->number]), ['body' => '¿Afecta a la demo?'])
            ->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseHas('issue_comments', ['issue_id' => $issue->id, 'user_id' => $this->observer->id]);

        // El comentario del observador se resalta y genera una alerta para el resto del equipo
        $this->get(route('issues.show', [$this->repo, $issue->number]))->assertSee('Observador')->assertSee('border-amber-500', false);
        $alert = $this->owner->fresh()->notifications->firstWhere('data.event', 'issue.commented');
        $this->assertSame('alert', $alert?->data['kind']);

        $this->get(route('issues.create', $this->repo))->assertForbidden();
        $this->post(route('issues.store', $this->repo), ['title' => 'Nueva'])->assertForbidden();
        $this->put(route('issues.update', [$this->repo, $issue->number]), ['status' => 'closed'])->assertForbidden();
        $this->delete(route('issues.destroy', [$this->repo, $issue->number]))->assertForbidden();
        $this->assertSame('open', $issue->fresh()->status);
    }

    public function test_collaborator_creates_updates_and_comments_an_issue(): void
    {
        $this->buildCollabProject();

        $this->actAs($this->dev)->get(route('issues.create', $this->repo))->assertOk()->assertSee('Crear issue');

        $this->post(route('issues.store', $this->repo), [
            'title' => 'El login no valida la contraseña',
            'description' => 'Pasos: entrar con cualquier clave.',
            'priority' => 'critical',
            'assignee_id' => $this->dev->id,
            'labels' => 'bug, seguridad, bug ,',
        ])->assertRedirect(route('issues.show', [$this->repo, 1]));

        $issue = Issue::where('repository_id', $this->repo->id)->where('number', 1)->firstOrFail();
        $this->assertSame(['bug', 'seguridad'], $issue->labels);
        $this->assertSame('critical', $issue->priority);

        $this->put(route('issues.update', [$this->repo, 1]), [
            'status' => 'in_progress',
            'priority' => 'high',
            'assignee_id' => $this->owner->id,
            'labels' => 'bug',
        ])->assertRedirect(route('issues.show', [$this->repo, 1]));

        $issue->refresh();
        $this->assertSame('in_progress', $issue->status);
        $this->assertSame($this->owner->id, $issue->assignee_id);
        $this->assertSame(['bug'], $issue->labels);

        $this->post(route('issues.comment', [$this->repo, 1]), ['body' => 'Me pongo con ello'])->assertRedirect();

        // Línea de tiempo: comentario y eventos del historial
        $this->get(route('issues.show', [$this->repo, 1]))
            ->assertOk()
            ->assertSee('Me pongo con ello')
            ->assertSee('cambió el estado de')
            ->assertSee('En progreso')
            ->assertSee('asignó la issue a')
            ->assertSee('quitó');
    }

    public function test_list_tabs_and_filters(): void
    {
        $this->buildCollabProject();
        $this->makeIssue($this->dev, ['title' => 'Bug de login', 'labels' => ['bug'], 'priority' => 'critical']);
        $this->makeIssue($this->dev, ['title' => 'Documentar API', 'labels' => ['docs'], 'priority' => 'low']);
        $closed = $this->makeIssue($this->dev, ['title' => 'Tarea vieja']);
        $closed->update(['status' => 'closed']);

        $this->actAs($this->observer);
        $this->get(route('issues.index', $this->repo))
            ->assertOk()->assertSee('Bug de login')->assertSee('Documentar API')->assertDontSee('Tarea vieja');
        $this->get(route('issues.index', [$this->repo, 'state' => 'closed']))
            ->assertOk()->assertSee('Tarea vieja')->assertDontSee('Bug de login');
        $this->get(route('issues.index', [$this->repo, 'label' => 'bug']))
            ->assertOk()->assertSee('Bug de login')->assertDontSee('Documentar API');
        $this->get(route('issues.index', [$this->repo, 'priority' => 'low', 'q' => 'Docu']))
            ->assertOk()->assertSee('Documentar API')->assertDontSee('Bug de login');
        $this->get(route('issues.index', [$this->repo, 'q' => 'nada que coincida']))
            ->assertOk()->assertSee('Ninguna issue coincide');
    }

    public function test_service_errors_are_shown_as_flash_messages(): void
    {
        $this->buildCollabProject();
        $issue = $this->makeIssue($this->dev);

        // Asignar a alguien que no es miembro: el servicio aborta con 422
        $this->actAs($this->dev)->from(route('issues.show', [$this->repo, $issue->number]))
            ->put(route('issues.update', [$this->repo, $issue->number]), ['assignee_id' => $this->outsider->id])
            ->assertRedirect(route('issues.show', [$this->repo, $issue->number]))
            ->assertSessionHas('error', 'El usuario indicado no pertenece al proyecto.');

        $this->post(route('issues.store', $this->repo), ['title' => ''])->assertSessionHasErrors('title');
    }

    public function test_owner_can_delete_an_issue(): void
    {
        $this->buildCollabProject();
        $issue = $this->makeIssue($this->dev);

        $this->actAs($this->owner)->delete(route('issues.destroy', [$this->repo, $issue->number]))
            ->assertRedirect(route('issues.index', $this->repo));
        $this->assertModelMissing($issue);
    }
}
