<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Models\Repository;
use App\Support\Visibility;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Repositorios públicos, accesibles sin sesión. Búsqueda por nombre, descripción o proyecto
 * y orden por actividad reciente, nombre o fecha de creación.
 */
class ExploreController extends Controller
{
    public const SORTS = ['activos' => 'Actividad reciente', 'nuevos' => 'Más nuevos', 'nombre' => 'Nombre (A-Z)'];

    public function __invoke(Request $request): View
    {
        $request->validate(['q' => 'nullable|string|max:100', 'orden' => 'nullable|in:'.implode(',', array_keys(self::SORTS))]);

        $term = trim((string) $request->query('q', ''));
        $sort = $request->query('orden', 'activos');

        $repositories = Repository::query()
            ->forListing()
            ->where('visibility', Visibility::PUBLIC)
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$term}%"))))
            ->with('project:id,name')
            ->withCount([
                'issues as open_issues_count' => fn ($q) => $q->whereIn('status', ['open', 'in_progress']),
                'pullRequests as open_pulls_count' => fn ($q) => $q->whereIn('status', ['open', 'in_review', 'approved', 'changes_requested']),
            ])
            ->when($sort === 'nombre', fn ($q) => $q->orderBy('name'))
            ->when($sort === 'nuevos', fn ($q) => $q->latest('created_at'))
            ->when($sort === 'activos', fn ($q) => $q->orderByDesc('last_commit_at')->orderByDesc('updated_at'))
            ->paginate(20)
            ->withQueryString();

        return view('explore.index', ['repositories' => $repositories, 'q' => $term, 'sort' => $sort, 'sorts' => self::SORTS]);
    }
}
