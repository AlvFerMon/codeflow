{{-- Paginación sencilla en español para un LengthAwarePaginator. --}}
@props(['paginator'])
@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="rounded-md px-3 py-1.5 text-slate-300">← Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="rounded-md px-3 py-1.5 text-indigo-600 hover:bg-slate-100">← Anterior</a>
        @endif
        <span class="text-slate-500">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="rounded-md px-3 py-1.5 text-indigo-600 hover:bg-slate-100">Siguiente →</a>
        @else
            <span class="rounded-md px-3 py-1.5 text-slate-300">Siguiente →</span>
        @endif
    </nav>
@endif
