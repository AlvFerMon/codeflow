<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use App\Models\Repository;
use App\Support\Visibility;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Repositorios públicos (sin sesión). Misma consulta que la API /explore/repositories. */
class ExploreController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate(['q' => 'nullable|string|max:100']);

        $term = trim((string) $request->query('q', ''));

        $repositories = Repository::query()
            ->where('visibility', Visibility::PUBLIC)
            ->when($term !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")
            ))
            ->with('project:id,name')
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('explore.index', ['repositories' => $repositories, 'q' => $term]);
    }
}
