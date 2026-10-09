@php($L = \App\Http\Controllers\Web\Collab\CollabLabels::class)
<x-app-layout :title="'Issues · '.$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'issues'])

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-collab-state-tabs :state="$state" :counts="$counts" :labels="['open' => 'Abiertas', 'closed' => 'Cerradas']"
            route="issues.index" :repository="$repository" />
        @can('create', [\App\Models\Issue::class, $repository])
            <x-button :href="route('issues.create', $repository)">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M8 3v10M3 8h10"/></svg>
                Nueva issue
            </x-button>
        @endcan
    </div>

    {{-- Filtros (GET) --}}
    <form method="GET" action="{{ route('issues.index', $repository) }}" class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
        <input type="hidden" name="state" value="{{ $state }}">
        <label class="col-span-2 sm:col-span-3 lg:col-span-2">
            <span class="sr-only">Buscar en el título</span>
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar en el título…"
                class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 px-3 py-2 text-sm shadow-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500">
        </label>
        <x-select name="status" :options="$L::ISSUE_STATUSES" :value="$filters['status']" placeholder="Estado" aria-label="Estado" />
        <x-select name="priority" :options="$L::PRIORITIES" :value="$filters['priority']" placeholder="Prioridad" aria-label="Prioridad" />
        <x-select name="assignee" :options="$members" :value="$filters['assignee']" placeholder="Asignada a" aria-label="Asignada a" />
        <x-select name="label" :options="$labels->mapWithKeys(fn ($l) => [$l => $l])->all()" :value="$filters['label']" placeholder="Etiqueta" aria-label="Etiqueta" />
        <div class="col-span-2 flex gap-2 sm:col-span-3 lg:col-span-6">
            <x-button variant="secondary">Filtrar</x-button>
            @if ($hasFilters)
                <x-button variant="ghost" :href="route('issues.index', [$repository, 'state' => $state])">Quitar filtros</x-button>
            @endif
        </div>
    </form>

    <x-card class="overflow-hidden">
        @if ($issues->isEmpty())
            @if ($hasFilters)
                <x-empty title="Ninguna issue coincide con los filtros">Prueba con otros criterios o quita los filtros.</x-empty>
            @elseif ($state === 'open')
                <x-empty title="No hay issues abiertas">
                    @can('create', [\App\Models\Issue::class, $repository])
                        Crea una con el botón «Nueva issue» para registrar un error o una tarea.
                    @else
                        Cuando el equipo registre errores o tareas aparecerán aquí.
                    @endcan
                </x-empty>
            @else
                <x-empty title="No hay issues cerradas">Las issues resueltas o cerradas aparecerán aquí.</x-empty>
            @endif
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($issues as $issue)
                    @include('issues._row', ['issue' => $issue, 'repository' => $repository])
                @endforeach
            </ul>
            <x-pagination :paginator="$issues" />
        @endif
    </x-card>
</x-app-layout>
