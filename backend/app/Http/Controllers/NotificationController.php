<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/** Bandeja de notificaciones del usuario autenticado. */
class NotificationController extends Controller
{
    /** Listado paginado (20). Con ?unread=1 solo las no leídas. */
    public function index(Request $request)
    {
        return $request->user()->notifications()
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->paginate(20)
            ->through(fn (DatabaseNotification $n) => $this->present($n));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return response()->json($this->present($notification));
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['updated' => $updated]);
    }

    private function present(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'kind' => $data['kind'] ?? 'change',
            'event' => $data['event'] ?? null,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'url' => $data['url'] ?? null,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
            'actor' => $data['actor'] ?? null,
            'project' => isset($data['project_id']) ? ['id' => $data['project_id'], 'name' => $data['project_name'] ?? null] : null,
            'repository_id' => $data['repository_id'] ?? null,
            'subject' => isset($data['subject_type']) ? ['type' => $data['subject_type'], 'id' => $data['subject_id'] ?? null] : null,
        ];
    }
}
