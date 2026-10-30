<?php

namespace App\Http\Controllers;

use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Descargar mis datos y borrar mi cuenta (RGPD). Mismo controlador para la web y la API.
 *
 * GET    /api/me/export   · /profile/export   → JSON con todos mis datos
 * DELETE /api/me          · /profile          → { password, confirm: "BORRAR" } borra la cuenta
 */
class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accounts) {}

    public function export(Request $request): Response
    {
        $data = $this->accounts->export($request->user());
        $name = 'codeflow-mis-datos-'.now()->format('Y-m-d').'.json';

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Cache-Control' => 'no-store',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $api = $request->expectsJson() || $request->is('api/*');
        $rules = [
            'password' => 'required|string',
            'confirm' => 'required|in:BORRAR',
        ];
        $messages = [
            'password.required' => 'Escribe tu contraseña.',
            'confirm.required' => 'Escribe BORRAR para confirmar.',
            'confirm.in' => 'Escribe BORRAR (en mayúsculas) para confirmar.',
        ];
        // En la web va en su propia bolsa de errores (el perfil tiene otro campo «password»)
        $api ? $request->validate($rules, $messages) : $request->validateWithBag('deleteAccount', $rules, $messages);

        $user = $request->user();
        $fail = function (string $field, string $message) use ($api) {
            $e = ValidationException::withMessages([$field => $message]);
            throw $api ? $e : $e->errorBag('deleteAccount');
        };
        if (! Hash::check($request->input('password'), $user->password)) {
            $fail('password', 'La contraseña no es correcta.');
        }
        try {
            $this->accounts->delete($user);
        } catch (HttpException $e) {
            $fail('password', $e->getMessage()); // proyectos sin otro propietario o último administrador
        }

        if ($api) {
            return response()->json(['message' => 'Cuenta borrada.']);
        }

        // Sin logout(): guardaría de nuevo el usuario (renueva el «recordarme») y lo volvería a crear
        Auth::guard('web')->forgetUser();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Tu cuenta y tus datos se han borrado.');
    }
}
