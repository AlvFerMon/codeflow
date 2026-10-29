<?php

namespace App\Http\Controllers;

use App\Services\DeviceService;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/** Dispositivos y sesiones abiertas (web: /profile/devices…, API: /api/me/devices…). */
class DeviceController extends Controller
{
    public function __construct(private DeviceService $devices) {}

    public function index(Request $request)
    {
        [$sessionId, $tokenId] = $this->current($request);
        $list = $this->devices->list($request->user(), $sessionId, $tokenId);

        if ($request->expectsJson()) {
            return response()->json([
                'web_supported' => $list['web_supported'],
                // En la API no se devuelve el id de las sesiones web (es la cookie de sesión)
                'web' => $list['web']->map(fn ($s) => collect($s)->except('id')->all())->values(),
                'apps' => $list['apps']->values(),
            ]);
        }

        return view('profile.devices', $list);
    }

    public function destroyToken(Request $request, int $id)
    {
        abort_unless($this->devices->closeToken($request->user(), $id), 404);

        return $this->done($request, 'Sesión de la app cerrada. Tendrá que volver a iniciar sesión.');
    }

    public function destroySession(Request $request, string $id)
    {
        abort_if($id === $request->session()->getId(), 422, 'Esta es la sesión que estás usando: usa «Cerrar sesión».');
        abort_unless($this->devices->closeSession($request->user(), $id), 404);

        return $this->done($request, 'Sesión del navegador cerrada.');
    }

    public function destroyOthers(Request $request)
    {
        [$sessionId, $tokenId] = $this->current($request);
        $closed = $this->devices->closeOthers($request->user(), $sessionId, $tokenId);

        return $this->done($request, match ($closed) {
            0 => 'No había otras sesiones abiertas.',
            1 => 'Se ha cerrado 1 sesión.',
            default => "Se han cerrado {$closed} sesiones.",
        });
    }

    /** @return array{0:?string,1:?int} sesión web y token desde los que se hace la petición */
    private function current(Request $request): array
    {
        $token = $request->user()->currentAccessToken();

        return [
            $request->hasSession() ? $request->session()->getId() : null,
            $token instanceof PersonalAccessToken ? $token->id : null,
        ];
    }

    private function done(Request $request, string $message)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : redirect()->route('profile.devices')->with('status', $message);
    }
}
