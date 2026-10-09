<x-app-layout :title="$project->name">
    @include('projects._header', ['project' => $project, 'active' => 'overview'])

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Descripción">
                <div class="p-4">
                    @if ($project->description)
                        <x-markdown :text="$project->description" />
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Este proyecto no tiene descripción.</p>
                    @endif
                </div>
            </x-card>

            <x-card :title="'Repositorios ('.$repositories->total().')'">
                @can('create', [\App\Models\Repository::class, $project])
                    <x-slot:actions>
                        <x-button :href="route('repositories.create', $project)" class="px-2.5! py-1.5! text-xs!">+ Nuevo repositorio</x-button>
                    </x-slot:actions>
                @endcan
                @if ($repositories->isEmpty())
                    <x-empty title="Todavía no hay repositorios"
                        :action="auth()->user()?->can('create', [\App\Models\Repository::class, $project]) ? 'Crear el primer repositorio' : null"
                        :href="route('repositories.create', $project)">
                        @can('create', [\App\Models\Repository::class, $project])
                            Un repositorio guarda el código y su historial. Crea el primero para empezar a subir archivos.
                        @else
                            El propietario aún no ha creado ningún repositorio.
                        @endcan
                    </x-empty>
                @else
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($repositories as $repository)
                            <li class="flex flex-col gap-2 px-4 py-3 transition-colors hover:bg-zinc-50/60 sm:flex-row sm:items-center sm:gap-3 dark:hover:bg-zinc-800/30">
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('repositories.show', $repository) }}" class="text-sm font-semibold text-zinc-900 hover:text-purple-600 dark:text-white dark:hover:text-purple-400">{{ $repository->name }}</a>
                                        <x-badge :variant="$repository->isPublic() ? 'success' : 'zinc'" size="xs">{{ $repository->isPublic() ? 'Público' : 'Privado' }}</x-badge>
                                        <x-badge variant="purple" size="xs"><x-code-icon name="branch" class="h-3 w-3" /> {{ $repository->default_branch }}</x-badge>
                                    </div>
                                    @if ($repository->description)
                                        <p class="line-clamp-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $repository->description }}</p>
                                    @endif
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $repository->branches_count }} {{ $repository->branches_count === 1 ? 'rama' : 'ramas' }} ·
                                        @if ($repository->last_commit_sha)
                                            último commit <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ substr($repository->last_commit_sha, 0, 7) }}</span>
                                            <time datetime="{{ $repository->last_commit_at?->toIso8601String() }}">{{ $repository->last_commit_at?->diffForHumans() }}</time>
                                        @else
                                            sin commits todavía
                                        @endif
                                    </p>
                                </div>
                                <x-button variant="secondary" size="sm" :href="route('repositories.show', $repository)" class="self-start sm:self-center">Abrir</x-button>
                            </li>
                        @endforeach
                    </ul>
                    <x-pagination :paginator="$repositories" />
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card :title="'Miembros ('.$membersCount.')'">
                <x-slot:actions>
                    <a href="{{ route('members.index', $project) }}" class="text-xs font-medium text-purple-600 dark:text-purple-400 hover:underline">
                        @can('manageMembers', $project) Gestionar @else Ver todos @endcan
                    </a>
                </x-slot:actions>
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($members as $member)
                        <li class="flex items-center gap-3 px-4 py-2.5">
                            <x-avatar :user="$member->user" />
                            <span class="min-w-0 flex-1 truncate text-sm text-zinc-800 dark:text-zinc-200">{{ $member->user?->name ?? 'Usuario eliminado' }}</span>
                            <x-account-role-badge :role="$member->role" />
                        </li>
                    @endforeach
                </ul>
                @if ($membersCount > $members->count())
                    <a href="{{ route('members.index', $project) }}" class="block border-t border-zinc-100 px-4 py-2 text-xs text-zinc-500 hover:text-purple-600 dark:border-zinc-800 dark:text-zinc-400">y {{ $membersCount - $members->count() }} más →</a>
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
