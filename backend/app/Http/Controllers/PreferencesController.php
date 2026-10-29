<?php

namespace App\Http\Controllers;

use App\Support\Preferences;
use Illuminate\Http\Request;

/** Preferencias de accesibilidad: API (/api/me/preferences) y web (/profile/preferences). */
class PreferencesController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(Preferences::for($request->user()));
    }

    public function update(Request $request)
    {
        $data = $request->validate(Preferences::rules(), [
            'theme.in' => 'Tema no válido.',
            'font_size.in' => 'Tamaño de letra no válido.',
        ]);

        // Desde el formulario web, las casillas sin marcar no se envían: se toman como «no»
        if (! $request->expectsJson() && $request->boolean('_form')) {
            foreach (['high_contrast', 'reduce_motion', 'underline_links', 'wrap_code'] as $flag) {
                $data[$flag] = $request->boolean($flag);
            }
        }

        $preferences = Preferences::update($request->user(), $data);

        return $request->expectsJson()
            ? response()->json($preferences)
            : redirect()->route('profile.edit')->with('status', 'Preferencias guardadas.');
    }
}
