<?php

namespace App\Http\Controllers;

use App\Services\OnboardingService;
use Illuminate\Http\Request;

/**
 * Tutorial de bienvenida. Lo usan la API (/api/me/onboarding…) y la web (/onboarding…):
 * con una petición JSON responde con el estado; desde un formulario vuelve a la página.
 */
class OnboardingController extends Controller
{
    public function __construct(private OnboardingService $onboarding) {}

    public function show(Request $request)
    {
        return response()->json($this->onboarding->state($request->user()));
    }

    public function progress(Request $request)
    {
        $data = $request->validate(['step' => 'required|integer|min:0']);
        $this->onboarding->progress($request->user(), (int) $data['step']);

        return $this->respond($request);
    }

    public function complete(Request $request)
    {
        $this->onboarding->completeTour($request->user());

        return $this->respond($request);
    }

    public function skip(Request $request)
    {
        $this->onboarding->skipTour($request->user());

        return $this->respond($request, 'Has saltado el tutorial. Puedes verlo cuando quieras desde tu menú › «Ver el tutorial otra vez».');
    }

    public function restart(Request $request)
    {
        $this->onboarding->restart($request->user());

        return $request->expectsJson()
            ? $this->show($request)
            : redirect()->route('home');
    }

    public function hideChecklist(Request $request)
    {
        $this->onboarding->hideChecklist($request->user());

        return $this->respond($request);
    }

    public function showChecklist(Request $request)
    {
        $this->onboarding->showChecklist($request->user());

        return $this->respond($request);
    }

    public function sampleProject(Request $request)
    {
        $project = $this->onboarding->createSampleProject($request->user());

        return $request->expectsJson()
            ? response()->json($this->onboarding->state($request->user()), 201)
            : redirect()->route('projects.show', $project)->with('status', 'Proyecto de práctica creado. ¡Prueba lo que quieras!');
    }

    private function respond(Request $request, ?string $status = null)
    {
        return $request->expectsJson()
            ? $this->show($request)
            : back()->with('status', $status);
    }
}
