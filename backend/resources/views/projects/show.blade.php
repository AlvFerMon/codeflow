<x-app-layout :title="$project->name">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('projects.index') }}" class="text-sm text-zinc-500 dark:text-zinc-400 hover:underline">← Proyectos</a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="break-words text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $project->name }}</h1>
                <x-account-role-badge :role="$role" />
                @if (auth()->user()->isAdmin() && ! auth()->user()->roleIn($project))
                    <x-badge color="amber">Acceso como admin</x-badge>
                @endif
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Propietario: {{ $project->owner?->name ?? '—' }} · Creado {{ $project->created_at?->diffForHumans() }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button variant="secondary" :href="route('members.index', $project)">Miembros</x-button>
            <x-button variant="secondary" :href="route('projects.activity', $project)">Actividad</x-button>
            @can('update', $project)
                <x-button variant="secondary" :href="route('projects.edit', $project)">Editar</x-button>
            @endcan
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Descripción">
                <div class="p-4">
                    @if ($project->description)
                        <div class="markdown">{!! \Illuminate\Support\Str::markdown($project->description, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Este proyecto no tiene descripción.</p>
                    @endif
                </div>
            </x-card>

            <x-card :title="'Repositorios ('.$repositories->count().')'">
                @can('create', [\App\Models\Repository::class, $project])
                    <x-slot:actions>
                        <x-button :href="route('repositories.create', $project)" class="px-2.5! py-1.5! text-xs!">+ Nuevo repositorio</x-button>
                    </x-slot:actions>
                @endcan
                @if ($repositories->isEmpty())
                    <x-empty title="Todavía no hay repositorios">
                        @can('create', [\App\Models\Repository::class, $project])
                            Crea el primero para empezar a subir código.
                        @else
                            El propietario aún no ha creado ningún repositorio.
                        @endcan
                    </x-empty>
                @else
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($repositories as $repository)
                            <li class="flex flex-col gap-1 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 sm:flex-row sm:items-center sm:gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('repositories.show', $repository) }}" class="font-mono text-sm font-semibold text-purple-700 dark:text-purple-300 hover:underline">{{ $repository->name }}</a>
                                        <x-badge :color="$repository->isPublic() ? 'green' : 'slate'">{{ $repository->isPublic() ? 'Público' : 'Privado' }}</x-badge>
                                    </div>
                                    @if ($repository->description)
                                        <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">{{ $repository->description }}</p>
                                    @endif
                                </div>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">Actualizado {{ $repository->updated_at?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card :title="'Miembros ('.$members->count().')'">
                <x-slot:actions>
                    <a href="{{ route('members.index', $project) }}" class="text-xs font-medium text-purple-600 dark:text-purple-400 hover:underline">
                        @can('manageMembers', $project) Gestionar @else Ver todos @endcan
                    </a>
                </x-slot:actions>
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($members->take(8) as $member)
                        <li class="flex items-center gap-3 px-4 py-2.5">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-zinc-200 dark:bg-zinc-700 text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ mb_strtoupper(mb_substr($member->user?->name ?? '?', 0, 1)) }}</span>
                            <span class="min-w-0 flex-1 truncate text-sm text-zinc-800 dark:text-zinc-200">{{ $member->user?->name ?? 'Usuario eliminado' }}</span>
                            <x-account-role-badge :role="$member->role" />
                        </li>
                    @endforeach
                </ul>
                @if ($members->count() > 8)
                    <p class="border-t border-zinc-100 dark:border-zinc-800 px-4 py-2 text-xs text-zinc-500 dark:text-zinc-400">y {{ $members->count() - 8 }} más…</p>
                @endif
            </x-card>

            <x-card title="Actividad reciente">
                <x-slot:actions>
                    <a href="{{ route('projects.activity', $project) }}" class="text-xs font-medium text-purple-600 dark:text-purple-400 hover:underline">Ver toda</a>
                </x-slot:actions>
                @if ($activity->isEmpty())
                    <x-empty title="Aún no hay actividad" class="py-8" />
                @else
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($activity as $item)
                            <x-account-activity-item :item="$item" />
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </aside>
    </div>
</x-app-layout>
