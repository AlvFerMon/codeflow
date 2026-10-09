{{-- Paginación sencilla de la versión móvil. Variable: $paginator (simplePaginate) --}}
@if ($paginator->hasPages())
    <div class="mt-4 flex justify-between gap-3 text-sm">
        @if ($paginator->previousPageUrl())
            <a href="{{ $paginator->previousPageUrl() }}" class="rounded-lg border border-zinc-200 bg-white px-4 py-2 font-medium dark:border-zinc-800 dark:bg-zinc-900">← Más recientes</a>
        @else
            <span></span>
        @endif
        @if ($paginator->nextPageUrl())
            <a href="{{ $paginator->nextPageUrl() }}" class="rounded-lg border border-zinc-200 bg-white px-4 py-2 font-medium dark:border-zinc-800 dark:bg-zinc-900">Ver más →</a>
        @endif
    </div>
@endif
