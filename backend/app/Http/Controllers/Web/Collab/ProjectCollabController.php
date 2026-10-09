<?php

namespace App\Http\Controllers\Web\Collab;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Project;
use App\Models\PullRequest;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Issues y pull requests de TODO el proyecto (agregan las de sus repositorios), con filtro por
 * repositorio y búsqueda por título. Paginado; los recuentos de las pestañas en una sola consulta.
 */
class ProjectCollabController extends Controller
{
    public function issues(Request $request, Project $project): View
    {
        return $this->listing($request, $project, 'issues');
    }

    public function pulls(Request $request, Project $project): View
    {
        return $this->listing($request, $project, 'pulls');
    }

    private function listing(Request $request, Project $project, string $kind): View
    {
        Gate::authorize('view', $project);

        $repositories = Repository::where('project_id', $project->id)->orderBy('name')->pluck('name', 'id');
        $repo = ctype_digit((string) $request->query('repo')) && $repositories->has((int) $request->query('repo')) ? (int) $request->query('repo') : null;
        $q = is_string($request->query('q')) ? trim(mb_substr($request->query('q'), 0, 100)) : '';
        $state = $request->query('state') === 'closed' ? 'closed' : 'open';

        [$model, $openStates] = $kind === 'issues'
            ? [Issue::class, CollabLabels::ISSUE_OPEN_STATES]
            : [PullRequest::class, PullRequest::OPEN_STATES];

        $base = fn (): Builder => $model::query()
            ->whereIn('repository_id', $repo ? [$repo] : $repositories->keys())
            ->when($q !== '', fn ($query) => $query->where('title', 'like', '%'.$q.'%'));

        $placeholders = implode(',', array_fill(0, count($openStates), '?'));
        $totals = $base()->toBase()
            ->selectRaw("SUM(CASE WHEN status IN ($placeholders) THEN 1 ELSE 0 END) as open_total", $openStates)
            ->selectRaw("SUM(CASE WHEN status IN ($placeholders) THEN 0 ELSE 1 END) as closed_total", $openStates)
            ->first();
        $counts = ['open' => (int) ($totals->open_total ?? 0), 'closed' => (int) ($totals->closed_total ?? 0)];

        $items = $base()
            ->when($state === 'open', fn ($query) => $query->whereIn('status', $openStates), fn ($query) => $query->whereNotIn('status', $openStates))
            ->with(['repository:id,name,project_id', 'author:id,name', $kind === 'issues' ? 'assignee:id,name' : 'reviewers:id,name'])
            ->withCount('comments')
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('projects.collab', [
            'project' => $project,
            'kind' => $kind,
            'items' => $items,
            'counts' => $counts,
            'state' => $state,
            'repositories' => $repositories,
            'repo' => $repo,
            'q' => $q,
        ]);
    }
}
