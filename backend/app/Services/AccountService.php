<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Commit;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\PullRequest;
use App\Models\PullRequestComment;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Derechos del RGPD sobre la propia cuenta: descargar mis datos (art. 15 y 20) y borrar la cuenta (art. 17).
 *
 * Al borrar la cuenta:
 * - Los proyectos en los que el usuario es el único miembro se borran del todo (con sus repositorios).
 * - En los proyectos compartidos se sale del proyecto; si los había creado él, pasan a otro propietario.
 * - Si es el ÚNICO propietario de un proyecto con más miembros no se puede borrar: antes tiene que
 *   nombrar otro propietario o borrar el proyecto (para no dejar a nadie sin su trabajo).
 * - Lo que escribió en proyectos de otros (commits, issues, comentarios) se queda, pero sin su nombre
 *   («usuario eliminado»): la base de datos pone el autor a NULL.
 */
class AccountService
{
    public function __construct(private readonly DeviceService $devices) {}

    /** @return array<string,mixed> */
    public function export(User $user): array
    {
        $projectNames = Project::withTrashed()
            ->whereIn('id', ProjectMember::where('user_id', $user->id)->select('project_id'))
            ->pluck('name', 'id');
        $devices = $this->devices->list($user);

        return [
            'generated_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'is_admin' => (bool) $user->is_admin,
                'preferences' => $user->preferences,
                'onboarding' => $user->onboarding,
            ],
            'projects' => ProjectMember::where('user_id', $user->id)->get()
                ->map(fn ($m) => ['project' => $projectNames[$m->project_id] ?? null, 'role' => $m->role, 'since' => $m->created_at?->toIso8601String()])
                ->values(),
            'commits' => Commit::where('author_id', $user->id)->with('repository:id,name')->orderBy('id')->get()
                ->map(fn (Commit $c) => ['repository' => $c->repository?->name, 'sha' => $c->sha, 'message' => $c->message, 'date' => $c->created_at?->toIso8601String()]),
            'issues' => Issue::where('author_id', $user->id)->with('repository:id,name')->orderBy('id')->get()
                ->map(fn (Issue $i) => ['repository' => $i->repository?->name, 'number' => $i->number, 'title' => $i->title, 'description' => $i->description, 'status' => $i->status, 'date' => $i->created_at?->toIso8601String()]),
            'issue_comments' => IssueComment::where('user_id', $user->id)->orderBy('id')->get(['issue_id', 'body', 'created_at'])
                ->map(fn ($c) => ['issue_id' => $c->issue_id, 'body' => $c->body, 'date' => $c->created_at?->toIso8601String()]),
            'pull_requests' => PullRequest::where('author_id', $user->id)->with('repository:id,name')->orderBy('id')->get()
                ->map(fn (PullRequest $p) => ['repository' => $p->repository?->name, 'number' => $p->number, 'title' => $p->title, 'description' => $p->description, 'status' => $p->status, 'date' => $p->created_at?->toIso8601String()]),
            'pull_request_comments' => PullRequestComment::where('user_id', $user->id)->orderBy('id')->get(['pull_request_id', 'body', 'path', 'created_at'])
                ->map(fn ($c) => ['pull_request_id' => $c->pull_request_id, 'body' => $c->body, 'path' => $c->path, 'date' => $c->created_at?->toIso8601String()]),
            'activity' => ActivityLog::where('user_id', $user->id)->orderBy('id')->get(['project_id', 'action', 'created_at'])
                ->map(fn ($a) => ['project' => $projectNames[$a->project_id] ?? null, 'action' => $a->action, 'date' => $a->created_at?->toIso8601String()]),
            'devices' => [
                'web_sessions' => $devices['web']->map(fn ($s) => ['browser' => $s['name'], 'ip' => $s['ip'], 'last_active_at' => $s['last_active_at']?->toIso8601String()])->values(),
                'apps' => $devices['apps']->map(fn ($t) => ['app' => $t['name'], 'created_at' => $t['created_at']?->toIso8601String(), 'last_active_at' => $t['last_active_at']?->toIso8601String()])->values(),
            ],
            'notifications' => $user->notifications()->latest()->limit(500)->get(['type', 'data', 'created_at', 'read_at'])
                ->map(fn ($n) => ['type' => class_basename($n->type), 'data' => $n->data, 'date' => $n->created_at?->toIso8601String(), 'read' => $n->read_at !== null]),
        ];
    }

    /**
     * Proyectos que impiden borrar la cuenta: soy su único propietario y hay más miembros.
     *
     * @return list<string> nombres de los proyectos
     */
    public function blockers(User $user): array
    {
        return $this->memberships($user)
            ->filter(fn (array $p) => $p['mine_owner'] && $p['other_owners'] === [] && $p['others'] !== [])
            ->map(fn (array $p) => $p['project']->name)
            ->values()->all();
    }

    public function delete(User $user): void
    {
        $blockers = $this->blockers($user);
        abort_if($blockers !== [], 422, 'Antes de borrar tu cuenta nombra otro propietario (o borra el proyecto) en: '.implode(', ', $blockers).'.');
        abort_if($user->is_admin && User::where('is_admin', true)->whereKeyNot($user->id)->doesntExist(), 422,
            'Eres el único administrador: nombra otro administrador antes de borrar tu cuenta.');

        DB::transaction(function () use ($user) {
            foreach ($this->memberships($user) as $p) {
                /** @var Project $project */
                $project = $p['project'];
                if ($p['others'] === []) {
                    $project->forceDelete(); // proyecto solo mío: fuera, con sus repositorios
                } elseif ($project->owner_id === $user->id) {
                    // Lo creé yo pero es compartido: pasa a otro propietario (o a otro miembro si no lo hay)
                    $project->forceFill(['owner_id' => $p['other_owners'][0] ?? $p['others'][0]])->saveQuietly();
                    if ($p['other_owners'] === []) {
                        ProjectMember::where('project_id', $project->id)->where('user_id', $p['others'][0])->update(['role' => Roles::OWNER]);
                    }
                }
            }

            // Proyectos creados por mí de los que ya no soy miembro (no deberían existir, pero el borrado en
            // cascada de projects.owner_id se los llevaría por delante)
            Project::withTrashed()->where('owner_id', $user->id)->each(function (Project $project) use ($user) {
                $heir = ProjectMember::where('project_id', $project->id)->where('user_id', '!=', $user->id)
                    ->orderByRaw("CASE role WHEN 'owner' THEN 0 ELSE 1 END")->value('user_id');
                $heir ? $project->forceFill(['owner_id' => $heir])->saveQuietly() : $project->forceDelete();
            });

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->tokens()->delete();
            $user->notifications()->delete();
            $user->delete();
        });
    }

    /** @return Collection<int, array{project:Project, mine_owner:bool, other_owners:list<int>, others:list<int>}> */
    private function memberships(User $user)
    {
        $projectIds = ProjectMember::where('user_id', $user->id)->pluck('project_id');
        $members = ProjectMember::whereIn('project_id', $projectIds)->get()->groupBy('project_id');

        return Project::withTrashed()->whereIn('id', $projectIds)->get()->map(function (Project $project) use ($members, $user) {
            $list = $members[$project->id] ?? collect();
            $others = $list->where('user_id', '!=', $user->id);

            return [
                'project' => $project,
                'mine_owner' => $list->contains(fn ($m) => $m->user_id === $user->id && $m->role === Roles::OWNER),
                'other_owners' => $others->where('role', Roles::OWNER)->pluck('user_id')->values()->all(),
                'others' => $others->pluck('user_id')->values()->all(),
            ];
        });
    }
}
