{{-- Paginación sencilla en español para un LengthAwarePaginator. --}}
@props(['paginator'])
@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 border-t border-zinc-200 dark:border-zinc-800 px-4 py-3 text-sm" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="rounded-lg px-3 py-1.5 text-zinc-300 dark:text-zinc-600">← Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="rounded-lg px-3 py-1.5 text-purple-600 dark:text-purple-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">← Anterior</a>
        @endif
        <span class="text-zinc-500 dark:text-zinc-400">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="rounded-lg px-3 py-1.5 text-purple-600 dark:text-purple-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">Siguiente →</a>
        @else
            <span class="rounded-lg px-3 py-1.5 text-zinc-300 dark:text-zinc-600">Siguiente →</span>
        @endif
    </nav>
@endif
