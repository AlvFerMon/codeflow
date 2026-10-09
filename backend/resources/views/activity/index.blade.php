<x-app-layout :title="'Actividad · '.$project->name">
    <div class="mb-6">
        <a href="{{ route('projects.show', $project) }}" class="text-sm text-zinc-500 dark:text-zinc-400 hover:underline">← {{ $project->name }}</a>
        <h1 class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Actividad del proyecto</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Todo lo que ha pasado en {{ $project->name }}, de lo más reciente a lo más antiguo.</p>
    </div>

    <x-card>
        @if ($items->isEmpty())
            <x-empty title="Aún no hay actividad en este proyecto" />
        @else
            <div class="border-b border-zinc-200 dark:border-zinc-800 px-4 py-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $logs->total() }} {{ $logs->total() === 1 ? 'evento' : 'eventos' }}</div>
            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($items as $item)
                    <x-account-activity-item :item="$item" />
                @endforeach
            </ul>
            <x-account-pagination :paginator="$logs" />
        @endif
    </x-card>
</x-app-layout>
