<?php

namespace App\Http\Controllers\Web\Collab\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Conversaciones largas: se cargan los 20 comentarios más recientes y, con ?antes={id}, los 20
 * anteriores a ese comentario. Los eventos y revisiones se limitan a la misma ventana de tiempo.
 */
trait PaginatesConversation
{
    protected int $conversationPageSize = 20;

    /**
     * @return array{comments: \Illuminate\Support\Collection, window: array{from: ?Carbon, to: ?Carbon}, olderId: ?int, newerUrl: bool, total: int}
     */
    protected function conversationPage(Request $request, HasMany $relation): array
    {
        $before = ctype_digit((string) $request->query('antes')) ? (int) $request->query('antes') : null;
        $total = (clone $relation)->count();

        $page = (clone $relation)
            ->with('user:id,name')
            ->when($before, fn ($q) => $q->where('id', '<', $before))
            ->orderByDesc('id')
            ->limit($this->conversationPageSize + 1)
            ->get();

        $hasOlder = $page->count() > $this->conversationPageSize;
        $comments = $page->take($this->conversationPageSize)->reverse()->values();

        $to = $before ? (clone $relation)->whereKey($before)->value('created_at') : null;

        return [
            'comments' => $comments,
            'window' => [
                // Sin comentarios más antiguos, la ventana empieza al principio de la conversación
                'from' => $hasOlder ? $comments->first()?->created_at : null,
                'to' => $to ? Carbon::parse($to) : null,
            ],
            'olderId' => $hasOlder ? $comments->first()?->id : null,
            'newerUrl' => $before !== null,
            'total' => $total,
        ];
    }

    /** ¿El momento $at cae dentro de la ventana de la página actual? */
    protected function inWindow($at, array $window): bool
    {
        return ($window['from'] === null || $at >= $window['from']) && ($window['to'] === null || $at < $window['to']);
    }
}
