<?php

namespace App\Http\Controllers\Web\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Bandeja de notificaciones del usuario (misma lógica que la API /me/notifications). */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $unreadOnly = $request->query('filter') === 'unread';
        $user = $request->user();

        $notifications = $user->notifications()
            ->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadOnly' => $unreadOnly,
            'unreadCount' => $user->unreadNotificationsCount(),
        ]);
    }

    /** Marca la notificación como leída y lleva al recurso (solo rutas relativas de la propia web). */
    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if (is_string($url) && self::isSafeRelativePath($url)) {
            return redirect($url);
        }

        return redirect()->route('notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $updated = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', $updated > 0 ? 'Todas las notificaciones están marcadas como leídas.' : 'No tenías notificaciones pendientes.');
    }

    /** "/algo" sí; "//dominio", "/\dominio", "https://..." o rutas con saltos de línea no (evita redirecciones abiertas). */
    private static function isSafeRelativePath(string $url): bool
    {
        return str_starts_with($url, '/')
            && ! str_starts_with($url, '//')
            && ! str_contains($url, '\\')
            && ! preg_match('/[\x00-\x1F\x7F]/', $url);
    }
}
