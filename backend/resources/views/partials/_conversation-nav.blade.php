{{-- Navegación de conversaciones largas (PaginatesConversation). Variables: $conversation, $url --}}
@if ($conversation['olderId'] || $conversation['newerUrl'])
    <div id="conversacion" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-dashed border-zinc-300 bg-zinc-50 px-4 py-2.5 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/40 dark:text-zinc-400">
        <span>Mostrando {{ $conversation['comments']->count() }} de {{ $conversation['total'] }} comentarios.</span>
        <span class="flex gap-3">
            @if ($conversation['olderId'])
                <a href="{{ $url }}?antes={{ $conversation['olderId'] }}#conversacion" class="font-semibold text-purple-600 hover:underline dark:text-purple-400">↑ Cargar comentarios anteriores</a>
            @endif
            @if ($conversation['newerUrl'])
                <a href="{{ $url }}#conversacion" class="font-semibold text-purple-600 hover:underline dark:text-purple-400">Ir a los más recientes ↓</a>
            @endif
        </span>
    </div>
@endif
