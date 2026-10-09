{{-- Paginación en español para un LengthAwarePaginator (única para toda la web). --}}
@props(['paginator'])
@if ($paginator->hasPages())
    @php
        $link = 'rounded-lg border border-zinc-300/80 bg-white px-3 py-1.5 text-zinc-700 shadow-xs transition-colors hover:bg-zinc-50 hover:text-zinc-950 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700';
        $off = 'rounded-lg border border-zinc-200 px-3 py-1.5 text-zinc-400 dark:border-zinc-800 dark:text-zinc-600';
    @endphp
    <nav {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 border-t border-zinc-100 px-4 py-3 text-xs dark:border-zinc-800']) }} aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="{{ $off }}" aria-disabled="true">← Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="{{ $link }}" rel="prev">← Anterior</a>
        @endif
        <span class="text-zinc-500 dark:text-zinc-400">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }} · {{ $paginator->total() }} en total</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="{{ $link }}" rel="next">Siguiente →</a>
        @else
            <span class="{{ $off }}" aria-disabled="true">Siguiente →</span>
        @endif
    </nav>
@endif
