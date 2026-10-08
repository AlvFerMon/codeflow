<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Página provisional para secciones de la web que aún no están implementadas.
 * Permite que toda la navegación funcione desde el principio.
 */
class PlaceholderController extends Controller
{
    public function __invoke(Request $request)
    {
        if (! $request->isMethod('GET')) {
            return back()->with('error', 'Esta acción todavía no está disponible.');
        }

        $repository = $request->route('repository');
        if ($repository instanceof Repository) {
            Gate::authorize('view', $repository);
        }

        return view('placeholder', [
            'title' => $request->route()->defaults['title'] ?? 'Próximamente',
            'repository' => $repository instanceof Repository ? $repository : null,
            'active' => $request->route()->defaults['tab'] ?? 'code',
        ]);
    }
}
