<x-app-layout title="Actividad">
    <div class="mb-6 flex flex-col justify-between gap-3 border-b border-zinc-200/80 pb-6 sm:flex-row sm:items-end dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">Actividad</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Lo que ha pasado en tus proyectos, de lo más reciente a lo más antiguo.</p>
        </div>
        <form method="GET" action="{{ route('activity.index') }}">
            <label for="project" class="sr-only">Proyecto</label>
            <select id="project" name="project" onchange="this.form.requestSubmit()" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">Todos mis proyectos</option>
                @foreach ($projects as $id => $name)
                    <option value="{{ $id }}" @selected($filter === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <noscript><x-button size="sm">Filtrar</x-button></noscript>
        </form>
    </div>

    <x-card>
        @if ($items->isEmpty())
            <x-empty title="Aún no hay actividad">Cuando tú o tu equipo trabajéis en los proyectos aparecerá aquí.</x-empty>
        @else
            <div class="border-b border-zinc-200 dark:border-zinc-800 px-4 py-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $logs->total() }} {{ $logs->total() === 1 ? 'evento' : 'eventos' }}</div>
            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($items as $item)
                    <x-account-activity-item :item="$item" :project-name="$projects[$item['project_id']] ?? null" :project-url="route('projects.show', $item['project_id'])" />
                @endforeach
            </ul>
            <x-pagination :paginator="$logs" />
        @endif
    </x-card>
</x-app-layout>
