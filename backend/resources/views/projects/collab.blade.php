@php
    $isIssues = $kind === 'issues';
    $noun = $isIssues ? 'issues' : 'pull requests';
    $route = $isIssues ? 'projects.issues' : 'projects.pulls';
    $labels = $isIssues ? ['open' => 'Abiertas', 'closed' => 'Cerradas'] : ['open' => 'Abiertos', 'closed' => 'Cerrados'];
@endphp
<x-app-layout :title="($isIssues ? 'Issues' : 'Pull requests').' · '.$project->name">
    @include('projects._header', ['project' => $project, 'active' => $kind])

    <x-help :summary="$isIssues ? '¿Qué es una issue?' : '¿Qué es un pull request?'" class="-mt-2 mb-4">
        @if ($isIssues)
            Una issue es una tarea, un error o una idea pendiente. Aquí ves las de todos los repositorios del proyecto; para crear una nueva, entra en el repositorio correspondiente.
        @else
            Un pull request es una propuesta para llevar los cambios de una rama a otra. Aquí ves los de todos los repositorios del proyecto.
        @endif
    </x-help>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="inline-flex rounded-lg border border-zinc-200 bg-white p-0.5 text-sm dark:border-zinc-800 dark:bg-zinc-900" role="tablist">
            @foreach ($labels as $value => $label)
                <a href="{{ route($route, array_filter([$project, 'state' => $value, 'repo' => $repo, 'q' => $q])) }}" role="tab" aria-selected="{{ $state === $value ? 'true' : 'false' }}"
                    @class(['rounded-md px-3 py-1 transition-colors', 'bg-zinc-100 font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-white' => $state === $value, 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => $state !== $value])>
                    {{ $label }} <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $counts[$value] }}</span>
                </a>
            @endforeach
        </div>
        <form method="GET" action="{{ route($route, $project) }}" class="flex flex-col gap-2 sm:flex-row" role="search">
            <input type="hidden" name="state" value="{{ $state }}">
            <label class="sr-only" for="repo">Repositorio</label>
            <select id="repo" name="repo" onchange="this.form.requestSubmit()" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">Todos los repositorios</option>
                @foreach ($repositories as $id => $name)
                    <option value="{{ $id }}" @selected($repo === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="q">Buscar por título</label>
            <input id="q" type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Buscar por título…"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm shadow-xs focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 dark:border-zinc-700 dark:bg-zinc-900">
            <x-button size="md">Filtrar</x-button>
        </form>
    </div>

    <x-card class="overflow-hidden">
        @if ($items->isEmpty())
            <x-empty :title="($repo || $q !== '') ? 'Ninguna coincide con los filtros' : ($state === 'open' ? 'No hay '.$noun.' abiert'.($isIssues ? 'as' : 'os') : 'No hay '.$noun.' cerrad'.($isIssues ? 'as' : 'os'))">
                @if ($repositories->isEmpty())
                    El proyecto todavía no tiene repositorios.
                @elseif ($repo || $q !== '')
                    Prueba con otros criterios o quita los filtros.
                @endif
            </x-empty>
        @else
            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($items as $item)
                    @if ($isIssues)
                        @include('issues._row', ['issue' => $item, 'repository' => $item->repository, 'showRepository' => true])
                    @else
                        @include('pulls._row', ['pull' => $item, 'repository' => $item->repository, 'showRepository' => true])
                    @endif
                @endforeach
            </ul>
            <x-pagination :paginator="$items" />
        @endif
    </x-card>
</x-app-layout>
