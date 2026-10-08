{{-- Paginación sencilla en español. Recibe un LengthAwarePaginator. --}}
@props(['paginator'])
@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="rounded-md border border-slate-200 px-3 py-1.5 text-slate-400">← Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-slate-700 hover:bg-slate-50" rel="prev">← Anterior</a>
        @endif
        <span class="text-slate-500">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-slate-700 hover:bg-slate-50" rel="next">Siguiente →</a>
        @else
            <span class="rounded-md border border-slate-200 px-3 py-1.5 text-slate-400">Siguiente →</span>
        @endif
    </nav>
@endif
