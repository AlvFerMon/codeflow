{{-- Paginación sencilla en español. Recibe un LengthAwarePaginator. --}}
@props(['paginator'])
@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 border-t border-zinc-200 dark:border-zinc-800 px-4 py-3 text-sm" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="rounded-lg border border-zinc-200 dark:border-zinc-800 px-3 py-1.5 text-zinc-400 dark:text-zinc-500">← Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/40" rel="prev">← Anterior</a>
        @endif
        <span class="text-zinc-500 dark:text-zinc-400">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/40" rel="next">Siguiente →</a>
        @else
            <span class="rounded-lg border border-zinc-200 dark:border-zinc-800 px-3 py-1.5 text-zinc-400 dark:text-zinc-500">Siguiente →</span>
        @endif
    </nav>
@endif
