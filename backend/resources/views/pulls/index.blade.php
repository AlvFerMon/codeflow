@php($L = \App\Http\Controllers\Web\Collab\CollabLabels::class)
<x-app-layout :title="'Pull requests · '.$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'pulls'])

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-collab-state-tabs :state="$state" :counts="$counts" :labels="['open' => 'Abiertos', 'closed' => 'Cerrados']"
            route="pulls.index" :repository="$repository" />
        @can('create', [\App\Models\PullRequest::class, $repository])
            <x-button :href="route('pulls.create', $repository)">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M8 3v10M3 8h10"/></svg>
                Nuevo pull request
            </x-button>
        @endcan
    </div>

    <form method="GET" action="{{ route('pulls.index', $repository) }}" class="mb-4 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-start">
        <input type="hidden" name="state" value="{{ $state }}">
        <x-select name="status" :options="$L::PR_STATUSES" :value="$filters['status']" placeholder="Estado" aria-label="Estado" class="sm:w-48" />
        <x-select name="author" :options="$authors" :value="$filters['author']" placeholder="Autor" aria-label="Autor" class="sm:w-48" />
        <div class="col-span-2 flex gap-2">
            <x-button variant="secondary">Filtrar</x-button>
            @if ($hasFilters)
                <x-button variant="ghost" :href="route('pulls.index', [$repository, 'state' => $state])">Quitar filtros</x-button>
            @endif
        </div>
    </form>

    <x-card class="overflow-hidden">
        @if ($pulls->isEmpty())
            @if ($hasFilters)
                <x-empty title="Ningún pull request coincide con los filtros">Prueba con otros criterios o quita los filtros.</x-empty>
            @elseif ($state === 'open')
                <x-empty title="No hay pull requests abiertos">
                    Un pull request propone fusionar los cambios de una rama en otra para revisarlos antes.
                </x-empty>
            @else
                <x-empty title="No hay pull requests cerrados">Los pull requests fusionados o cerrados aparecerán aquí.</x-empty>
            @endif
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($pulls as $pull)
                    <li class="flex gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                        <x-collab-status-icon type="pull" :status="$pull->status" class="mt-0.5 h-5 w-5" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <a href="{{ route('pulls.show', [$repository, $pull->number]) }}" class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-purple-600 dark:hover:text-purple-400">
                                    <span class="text-zinc-500 dark:text-zinc-400">#{{ $pull->number }}</span> {{ $pull->title }}
                                </a>
                                <x-collab-pr-status :status="$pull->status" />
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                <span class="inline-flex max-w-full items-center gap-1 font-mono text-[11px]">
                                    <span class="truncate rounded bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 text-zinc-700 dark:text-zinc-300">{{ $pull->source_branch_name }}</span>
                                    <span aria-label="hacia">→</span>
                                    <span class="truncate rounded bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 text-zinc-700 dark:text-zinc-300">{{ $pull->target_branch_name }}</span>
                                </span>
                                <span>de <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $pull->author?->name ?? 'usuario eliminado' }}</span>
                                    <time datetime="{{ $pull->created_at->toIso8601String() }}" title="{{ $pull->created_at->format('d/m/Y H:i') }}">{{ $pull->created_at->diffForHumans() }}</time></span>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-start gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                            @if ($pull->reviewers->isNotEmpty())
                                <span class="flex -space-x-1.5" title="Revisores: {{ $pull->reviewers->pluck('name')->join(', ') }}">
                                    @foreach ($pull->reviewers->take(3) as $reviewer)
                                        <x-collab-avatar :user="$reviewer" size="sm" class="ring-2 ring-white dark:ring-zinc-900" />
                                    @endforeach
                                </span>
                            @endif
                            @if ($pull->comments_count > 0)
                                <span class="inline-flex items-center gap-1" title="{{ $pull->comments_count }} comentarios">
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linejoin="round" d="M2.5 3.5h11v7h-6l-3 2.5v-2.5h-2z"/></svg>
                                    {{ $pull->comments_count }}
                                </span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <x-collab-pagination :paginator="$pulls" />
        @endif
    </x-card>
</x-app-layout>
