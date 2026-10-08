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
                class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
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
            <ul class="divide-y divide-slate-200">
                @foreach ($issues as $issue)
                    <li class="flex gap-3 px-4 py-3 hover:bg-slate-50">
                        <x-collab-status-icon type="issue" :status="$issue->status" class="mt-0.5 h-5 w-5" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <a href="{{ route('issues.show', [$repository, $issue->number]) }}" class="font-medium text-slate-900 hover:text-indigo-600">
                                    <span class="text-slate-500">#{{ $issue->number }}</span> {{ $issue->title }}
                                </a>
                                @foreach ($issue->labels ?? [] as $label)
                                    <x-collab-label :label="$label" />
                                @endforeach
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                <x-collab-issue-status :status="$issue->status" />
                                <x-collab-priority :priority="$issue->priority" />
                                <span>abierta por <span class="font-medium text-slate-700">{{ $issue->author?->name ?? 'usuario eliminado' }}</span>
                                    <time datetime="{{ $issue->created_at->toIso8601String() }}" title="{{ $issue->created_at->format('d/m/Y H:i') }}">{{ $issue->created_at->diffForHumans() }}</time></span>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-start gap-3 text-xs text-slate-500">
                            @if ($issue->assignee)
                                <x-collab-avatar :user="$issue->assignee" size="sm" />
                            @endif
                            @if ($issue->comments_count > 0)
                                <span class="inline-flex items-center gap-1" title="{{ $issue->comments_count }} comentarios">
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linejoin="round" d="M2.5 3.5h11v7h-6l-3 2.5v-2.5h-2z"/></svg>
                                    {{ $issue->comments_count }}
                                </span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <x-collab-pagination :paginator="$issues" />
        @endif
    </x-card>
</x-app-layout>
