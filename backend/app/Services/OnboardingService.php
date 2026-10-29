<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tutorial de bienvenida: visita guiada (pasos en config/onboarding.php) y lista «Primeros pasos».
 *
 * El estado se guarda en users.onboarding (json):
 *   tour_status        pending | in_progress | completed | skipped
 *   tour_step          índice del paso en el que se quedó
 *   tour_finished_at   cuándo terminó o la saltó
 *   checklist_hidden_until  la lista se oculta unos días y vuelve mientras no esté completa
 *   steps_done         {id: fecha} tareas ya detectadas (se guardan para no perderlas si se
 *                      borra el proyecto donde se hicieron)
 *   sample_project_id  proyecto de práctica (sus acciones no cuentan para la lista)
 */
class OnboardingService
{
    public const TOUR_PENDING = 'pending';

    public const TOUR_IN_PROGRESS = 'in_progress';

    public const TOUR_COMPLETED = 'completed';

    public const TOUR_SKIPPED = 'skipped';

    public function __construct(
        private ProjectService $projects,
        private RepositoryService $repositories,
        private IssueService $issues,
        private PullRequestService $pullRequests,
    ) {}

    /** Estado ya calculado, por petición y usuario (la cabecera y el inicio lo piden en la misma petición). */
    private \WeakMap $cache;

    /** Estado completo para pintar la visita y la lista (web, Android y escritorio). */
    public function state(User $user): array
    {
        $this->cache ??= new \WeakMap;
        $request = request();
        $perRequest = $this->cache[$request] ?? [];

        if (! isset($perRequest[$user->id])) {
            $perRequest[$user->id] = $this->buildState($user);
            $this->cache[$request] = $perRequest;
        }

        return $perRequest[$user->id];
    }

    private function buildState(User $user): array
    {
        $data = $this->detect($user);
        $tourStatus = $data['tour_status'] ?? self::TOUR_PENDING;
        $steps = config('onboarding.tour');

        $items = [];
        foreach (config('onboarding.checklist') as $item) {
            $doneAt = $data['steps_done'][$item['id']] ?? null;
            $items[] = [
                'id' => $item['id'],
                'title' => $item['title'],
                'text' => $item['text'],
                'url' => isset($item['route']) ? route($item['route']) : null,
                'optional' => (bool) ($item['optional'] ?? false),
                'done' => $doneAt !== null,
                'done_at' => $doneAt,
            ];
        }

        $required = array_filter($items, fn ($i) => ! $i['optional']);
        $requiredDone = count(array_filter($required, fn ($i) => $i['done']));
        $completed = $requiredDone === count($required);
        $hiddenUntil = isset($data['checklist_hidden_until']) ? Carbon::parse($data['checklist_hidden_until']) : null;
        $sampleId = $data['sample_project_id'] ?? null;
        $sample = $sampleId ? Project::find($sampleId) : null;

        return [
            'tour' => [
                'status' => $tourStatus,
                'show' => $user->hasVerifiedEmail() && in_array($tourStatus, [self::TOUR_PENDING, self::TOUR_IN_PROGRESS], true),
                'step' => min((int) ($data['tour_step'] ?? 0), max(count($steps) - 1, 0)),
                'steps' => array_map(fn ($s) => [
                    'id' => $s['id'], 'title' => $s['title'], 'text' => $s['text'], 'anchor' => $s['anchor'] ?? null,
                ], $steps),
            ],
            'checklist' => [
                'visible' => ! $completed && (! $hiddenUntil || $hiddenUntil->isPast()),
                'completed' => $completed,
                'done' => count(array_filter($items, fn ($i) => $i['done'])),
                'total' => count($items),
                'required_done' => $requiredDone,
                'required_total' => count($required),
                'hidden_until' => $hiddenUntil?->toIso8601String(),
                'items' => $items,
            ],
            'sample_project' => [
                'id' => $sample?->id,
                'url' => $sample ? route('projects.show', $sample) : null,
            ],
        ];
    }

    /** Guarda en qué paso de la visita va (para continuar si recarga o cambia de dispositivo). */
    public function progress(User $user, int $step): void
    {
        $max = count(config('onboarding.tour')) - 1;
        $this->save($user, [
            'tour_status' => self::TOUR_IN_PROGRESS,
            'tour_step' => max(0, min($step, $max)),
        ]);
    }

    public function completeTour(User $user): void
    {
        $this->save($user, ['tour_status' => self::TOUR_COMPLETED, 'tour_finished_at' => now()->toIso8601String()]);
    }

    public function skipTour(User $user): void
    {
        $this->save($user, ['tour_status' => self::TOUR_SKIPPED, 'tour_finished_at' => now()->toIso8601String()]);
    }

    /** «Ver el tutorial otra vez»: vuelve a empezar la visita y muestra la lista. Las tareas hechas se conservan. */
    public function restart(User $user): void
    {
        $this->save($user, [
            'tour_status' => self::TOUR_PENDING,
            'tour_step' => 0,
            'tour_finished_at' => null,
            'checklist_hidden_until' => null,
        ]);
    }

    public function hideChecklist(User $user): void
    {
        $days = (int) config('onboarding.checklist_snooze_days', 7);
        $this->save($user, ['checklist_hidden_until' => now()->addDays($days)->toIso8601String()]);
    }

    public function showChecklist(User $user): void
    {
        $this->save($user, ['checklist_hidden_until' => null]);
    }

    /**
     * Crea (una sola vez) un proyecto de práctica con un repositorio, una rama con un cambio,
     * una issue y un pull request, usando los mismos servicios que la aplicación.
     */
    public function createSampleProject(User $user): Project
    {
        $existing = ($id = $this->data($user)['sample_project_id'] ?? null) ? Project::find($id) : null;
        if ($existing) {
            return $existing;
        }

        $project = DB::transaction(function () use ($user) {
            $project = $this->projects->create($user, [
                'name' => 'Proyecto de práctica',
                'description' => 'Proyecto de ejemplo para aprender a usar CodeFlow. Puedes tocarlo todo o borrarlo cuando quieras.',
            ]);
            // Se guarda ya para que las acciones siguientes no cuenten en «Primeros pasos»
            $this->save($user, ['sample_project_id' => $project->id]);

            $repository = $this->repositories->create($project, $user, [
                'name' => 'mi-primer-repositorio',
                'description' => 'Repositorio de ejemplo',
                'init_readme' => false,
            ]);
            $main = Branch::where('repository_id', $repository->id)->where('name', 'main')->firstOrFail();

            $this->repositories->commitChanges($main, $user, 'Primer commit: README y una página de ejemplo', [
                ['path' => 'README.md', 'content' => "# Mi primer repositorio\n\nEste es un repositorio de práctica.\n\n## Qué probar\n\n- Abre `index.html` y edítalo.\n- Mira el historial de commits.\n- Revisa el pull request abierto y fusiónalo.\n"],
                ['path' => 'index.html', 'content' => "<!doctype html>\n<html lang=\"es\">\n<head><meta charset=\"utf-8\"><title>Hola</title></head>\n<body>\n  <h1>Hola, CodeFlow</h1>\n</body>\n</html>\n"],
            ]);

            $branch = $this->repositories->createBranch($repository, $user, 'mejora-portada', $main->id);
            $this->repositories->commitChanges($branch, $user, 'Añade un párrafo a la portada', [
                ['path' => 'index.html', 'content' => "<!doctype html>\n<html lang=\"es\">\n<head><meta charset=\"utf-8\"><title>Hola</title></head>\n<body>\n  <h1>Hola, CodeFlow</h1>\n  <p>Este cambio se hizo en la rama «mejora-portada».</p>\n</body>\n</html>\n"],
            ]);

            $issue = $this->issues->create($repository, $user, [
                'title' => 'La portada necesita un texto de bienvenida',
                'description' => 'Ejemplo de issue: sirve para apuntar tareas o errores. Ciérrala cuando esté resuelta.',
                'priority' => 'low',
            ]);

            $this->pullRequests->open($repository, $user, [
                'title' => 'Añade un texto de bienvenida a la portada',
                'description' => 'Ejemplo de pull request: propone llevar los cambios de «mejora-portada» a «main». Revisa los cambios y pulsa «Fusionar».',
                'source_branch_id' => $branch->id,
                'target_branch_id' => $main->id,
                'issues' => [$issue->id],
            ]);

            return $project;
        });

        return $project;
    }

    // ── Internos ────────────────────────────────────────────────────────────

    private function data(User $user): array
    {
        $raw = $user->onboarding;

        return is_array($raw) ? $raw : [];
    }

    private function save(User $user, array $changes): void
    {
        if (isset($this->cache)) {
            unset($this->cache[request()]);
        }
        $user->forceFill(['onboarding' => array_merge($this->data($user), $changes)])->save();
    }

    /** Busca tareas nuevas hechas desde la última vez y las guarda. */
    private function detect(User $user): array
    {
        $data = $this->data($user);
        $done = $data['steps_done'] ?? [];
        $pending = array_filter(config('onboarding.checklist'), fn ($i) => ! isset($done[$i['id']]));

        if (! $pending) {
            return $data;
        }

        $actions = collect($pending)->pluck('activity')->filter()->flatten()->unique()->values()->all();
        $found = $actions
            ? ActivityLog::where('user_id', $user->id)
                ->whereIn('action', $actions)
                ->when($data['sample_project_id'] ?? null, fn ($q, $id) => $q->where('project_id', '!=', $id))
                ->selectRaw('action, MIN(created_at) as first_at')
                ->groupBy('action')
                ->pluck('first_at', 'action')
            : collect();

        $changed = false;
        foreach ($pending as $item) {
            $at = null;
            foreach ($item['activity'] ?? [] as $action) {
                if (isset($found[$action])) {
                    $at = Carbon::parse($found[$action])->toIso8601String();
                    break;
                }
            }
            if (! $at && ! empty($item['tokens'])) {
                $token = $user->tokens()
                    ->where(function ($q) use ($item) {
                        foreach ($item['tokens'] as $pattern) {
                            $q->orWhere('name', 'like', $pattern);
                        }
                    })
                    ->oldest()->first();
                $at = $token?->created_at?->toIso8601String();
            }
            if ($at) {
                $done[$item['id']] = $at;
                $changed = true;
            }
        }

        if ($changed) {
            $data['steps_done'] = $done;
            $user->forceFill(['onboarding' => $data])->save();
        }

        return $data;
    }
}
