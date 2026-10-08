{{--
    Comentario de una issue o de un PR. Props: comment (con user cargado), role (rol del autor en el proyecto),
    id (ancla). Slot "meta": información extra en la cabecera (p. ej. "en `archivo` línea N" o el estado de una revisión).
--}}
@props(['comment', 'role' => null, 'anchor' => null, 'empty' => 'Sin comentario.'])
@php($observer = $role === \App\Support\Roles::OBSERVER)
<article @if ($anchor) id="{{ $anchor }}" @endif {{ $attributes->class([
    'scroll-mt-4 overflow-hidden rounded-lg border bg-white',
    'border-amber-300 border-l-4 border-l-amber-500' => $observer,
    'border-slate-200' => ! $observer,
]) }}>
    <header @class([
        'flex flex-wrap items-center gap-x-2 gap-y-1 border-b px-4 py-2 text-sm',
        'border-amber-200 bg-amber-50' => $observer,
        'border-slate-200 bg-slate-50' => ! $observer,
    ])>
        <x-collab-avatar :user="$comment->user" size="sm" />
        <span class="font-medium text-slate-900">{{ $comment->user?->name ?? 'Usuario eliminado' }}</span>
        @if ($observer)
            <x-badge color="amber" title="Comentario de un observador: genera una alerta para el equipo">
                <svg class="h-3 w-3" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 3C4.4 3 1.7 5.6 1 8c.7 2.4 3.4 5 7 5s6.3-2.6 7-5c-.7-2.4-3.4-5-7-5Zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>
                Observador
            </x-badge>
        @elseif ($role === \App\Support\Roles::OWNER)
            <x-badge color="indigo">Propietario</x-badge>
        @endif
        {{ $meta ?? '' }}
        <time class="text-slate-500" datetime="{{ $comment->created_at?->toIso8601String() }}" title="{{ $comment->created_at?->format('d/m/Y H:i') }}">{{ $comment->created_at?->diffForHumans() }}</time>
    </header>
    <div class="px-4 py-3">
        <x-collab-markdown :text="$comment->body" :empty="$empty" />
    </div>
</article>
