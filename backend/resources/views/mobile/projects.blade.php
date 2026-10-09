<x-mobile-layout title="Proyectos" tab="projects">
    @if ($projects->isEmpty())
        <x-card><x-empty title="Todavía no participas en ningún proyecto">Cuando te añadan a uno aparecerá aquí.</x-empty></x-card>
    @else
        <ul class="space-y-2">
            @foreach ($projects as $project)
                <li>
                    <a href="{{ route('mobile.activity', ['project' => $project->id]) }}" class="block rounded-xl border border-zinc-200/80 bg-white px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate font-semibold">{{ $project->name }}</span>
                            <x-account-role-badge :role="$project->my_role" />
                        </div>
                        @if ($project->description)
                            <p class="mt-1 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $project->description }}</p>
                        @endif
                        <p class="mt-2 text-[11px] text-zinc-500 dark:text-zinc-400">
                            {{ $project->repositories_count }} {{ $project->repositories_count === 1 ? 'repositorio' : 'repositorios' }}
                            · {{ $project->members_count }} {{ $project->members_count === 1 ? 'miembro' : 'miembros' }}
                            · actualizado {{ $project->updated_at?->diffForHumans() }}
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-center text-xs text-zinc-500 dark:text-zinc-400">Pulsa un proyecto para ver su actividad.</p>
    @endif
</x-mobile-layout>
