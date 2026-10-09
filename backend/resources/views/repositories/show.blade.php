<x-app-layout :title="$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'code'])

    @php
        $branchName = $branch?->name;
        $branchParam = $branchName && $branchName !== $repository->default_branch ? $branchName : null;
        // Versión mostrada: un commit concreto (?commit=) o la rama (?branch=)
        $refParams = $atCommit ? ['commit' => substr($atCommit->sha, 0, 12)] : ['branch' => $branchParam];
        $parentPath = str_contains($path, '/') ? dirname($path) : '';
    @endphp

    @if ($atCommit)
        <div class="mb-4 flex flex-col gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center dark:text-amber-200" role="status">
            <x-code-icon name="history" class="h-5 w-5" />
            <span>Estás viendo el código tal como estaba en el commit
                <a href="{{ route('commits.show', [$repository, $atCommit->sha]) }}" class="font-mono font-semibold underline">{{ substr($atCommit->sha, 0, 7) }}</a>
                ({{ $atCommit->created_at->format('d/m/Y H:i') }}). Es solo lectura.</span>
            <a href="{{ route('repositories.show', [$repository]) }}" class="font-semibold underline-offset-2 hover:underline sm:ml-auto">Volver a la versión actual →</a>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_16rem]">
        <div class="min-w-0 space-y-4">
            {{-- Barra de herramientas --}}
            <div class="flex flex-wrap items-center gap-3">
                @if ($branch && ! $atCommit)
                    <x-code-branch-select :action="route('repositories.show', $repository)" :branches="$branches" :current="$branchName" :params="['path' => $path]" />
                @endif
                <x-code-breadcrumbs :repository="$repository" :branch="$branch" :commit="$atCommit" :path="$path" class="min-w-0 flex-1" />

                @if ($branch && ! $atCommit)
                    @can('push', $repository)
                        <div class="flex flex-wrap gap-2">
                            <x-button variant="secondary" class="py-1.5" :href="route('files.create', array_filter([$repository, 'mode' => 'upload', 'branch' => $branchName, 'path' => $path]))">
                                <x-code-icon name="upload" /> Subir archivos o carpetas
                            </x-button>
                            <x-button variant="secondary" class="py-1.5" :href="route('files.create', array_filter([$repository, 'mode' => 'new', 'branch' => $branchName, 'path' => $path]))">
                                <x-code-icon name="plus" /> Nuevo archivo
                            </x-button>
                            <x-button variant="secondary" class="py-1.5" :href="route('files.create', array_filter([$repository, 'mode' => 'folder', 'branch' => $branchName, 'path' => $path]))">
                                <x-code-icon name="folder-plus" /> Nueva carpeta
                            </x-button>
                        </div>
                    @endcan
                @endif
            </div>

            <x-card class="overflow-hidden">
                {{-- Último commit de la rama --}}
                @if ($lastCommit)
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 px-4 py-3 text-sm">
                        <x-avatar :user="$lastCommit->author" />
                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $lastCommit->author?->name ?? 'Usuario eliminado' }}</span>
                        <a href="{{ route('commits.show', [$repository, $lastCommit->sha]) }}" class="min-w-0 flex-1 truncate text-zinc-600 dark:text-zinc-400 hover:text-purple-600 dark:hover:text-purple-400 hover:underline" title="{{ $lastCommit->message }}">{{ \Illuminate\Support\Str::before((string) $lastCommit->message, "\n") }}</a>
                        <span class="flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                            <a href="{{ route('commits.show', [$repository, $lastCommit->sha]) }}" class="font-mono text-zinc-600 dark:text-zinc-400 hover:text-purple-600 dark:hover:text-purple-400 hover:underline">{{ substr($lastCommit->sha, 0, 7) }}</a>
                            <time datetime="{{ $lastCommit->created_at->toIso8601String() }}" title="{{ $lastCommit->created_at->format('d/m/Y H:i') }}">{{ $lastCommit->created_at->diffForHumans() }}</time>
                            <a href="{{ route('commits.index', array_filter([$repository, 'branch' => $branchParam])) }}" class="inline-flex items-center gap-1 font-medium text-zinc-700 dark:text-zinc-300 hover:text-purple-600 dark:hover:text-purple-400">
                                <x-code-icon name="history" /> {{ $commitsCount }} {{ $commitsCount === 1 ? 'commit' : 'commits' }}
                            </a>
                        </span>
                    </div>
                @endif

                @if (! $branch || ! $branch->head_commit_id)
                    <x-empty title="Este repositorio está vacío">
                        @if ($branch)
                            La rama <span class="font-mono">{{ $branchName }}</span> todavía no tiene commits.
                            @can('push', $repository)
                                <div class="mt-4 flex flex-wrap justify-center gap-2">
                                    <x-button :href="route('files.create', array_filter([$repository, 'mode' => 'upload', 'branch' => $branchName]))"><x-code-icon name="upload" /> Subir archivos o carpetas</x-button>
                                    <x-button variant="secondary" :href="route('files.create', array_filter([$repository, 'mode' => 'new', 'branch' => $branchName]))"><x-code-icon name="plus" /> Crear un archivo</x-button>
                                </div>
                            @else
                                Cuando alguien suba código aparecerá aquí.
                            @endcan
                        @else
                            El repositorio no tiene ramas.
                        @endif
                    </x-empty>
                @elseif (empty($entries) && $path === '')
                    <x-empty title="No hay archivos en esta rama" />
                @else
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @if ($path !== '')
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                    <td class="px-4 py-2" colspan="2">
                                        <a href="{{ route('repositories.show', array_filter([$repository, ...$refParams, 'path' => $parentPath])) }}" class="block font-mono text-zinc-500 dark:text-zinc-400 hover:text-purple-600 dark:hover:text-purple-400" title="Carpeta superior">..</a>
                                    </td>
                                </tr>
                            @endif
                            @forelse ($entries as $entry)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                    <td class="px-4 py-2">
                                        @if ($entry['type'] === 'dir')
                                            <a href="{{ route('repositories.show', array_filter([$repository, ...$refParams, 'path' => $entry['path']])) }}" class="flex items-center gap-2 text-zinc-800 dark:text-zinc-200 hover:text-purple-600 dark:hover:text-purple-400 hover:underline">
                                                <x-code-icon name="folder" class="h-4 w-4 text-sky-500" />
                                                <span class="break-all">{{ $entry['name'] }}</span>
                                            </a>
                                        @else
                                            <a href="{{ route('repositories.blob', array_filter([$repository, ...$refParams, 'path' => $entry['path']])) }}" class="flex items-center gap-2 text-zinc-800 dark:text-zinc-200 hover:text-purple-600 dark:hover:text-purple-400 hover:underline">
                                                <x-code-icon name="file" class="h-4 w-4 text-zinc-400 dark:text-zinc-500" />
                                                <span class="break-all">{{ $entry['name'] }}</span>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-xs text-zinc-500 dark:text-zinc-400">
                                        @if ($entry['type'] === 'file')
                                            <x-code-size :bytes="$sizes[$entry['blob_id']] ?? 0" />
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400" colspan="2">Esta carpeta está vacía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </x-card>

            @if ($path !== '' && $branch)
                @can('push', $repository)
                    <details class="rounded-lg border border-rose-200 dark:border-rose-500/30 bg-white dark:bg-zinc-900">
                        <summary class="cursor-pointer px-4 py-2 text-sm font-medium text-rose-700 dark:text-rose-300">Eliminar esta carpeta</summary>
                        <form method="POST" action="{{ route('files.destroy', $repository) }}" class="space-y-3 border-t border-rose-100 dark:border-rose-500/30 p-4"
                            data-confirm-title="Eliminar la carpeta" data-confirm-button="Eliminar carpeta" data-confirm="Se creará un commit que elimina «{{ $path }}» y todo su contenido de esta rama. El historial anterior se conserva.">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="branch" value="{{ $branchName }}">
                            <input type="hidden" name="path" value="{{ $path }}">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">Se eliminarán todos los archivos de <span class="font-mono">{{ $path }}</span> en la rama <span class="font-mono">{{ $branchName }}</span> con un nuevo commit. El historial anterior se conserva.</p>
                            <x-input name="message" label="Mensaje del commit" :placeholder="'Eliminar carpeta '.$path" maxlength="500" />
                            <x-button variant="danger"><x-code-icon name="trash" /> Eliminar carpeta</x-button>
                        </form>
                    </details>
                @endcan
            @endif

            @if ($readme)
                <x-card :title="$readme['name']">
                    <x-slot:actions>
                        <a href="{{ route('repositories.blob', array_filter([$repository, ...$refParams, 'path' => $readme['path']])) }}" class="text-xs text-purple-600 dark:text-purple-400 hover:underline">Ver archivo</a>
                    </x-slot:actions>
                    <div class="p-4 sm:p-6">
                        @if ($readme['markdown'])
                            <x-markdown :text="$readme['content']" />
                        @else
                            <pre class="overflow-x-auto whitespace-pre-wrap font-mono text-sm text-zinc-700 dark:text-zinc-300">{{ $readme['content'] }}</pre>
                        @endif
                    </div>
                </x-card>
            @endif
        </div>

        {{-- Información del repositorio --}}
        <aside class="space-y-4">
            <x-card title="Acerca de">
                @if ($repository->description)
                    <p class="px-4 pt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $repository->description }}</p>
                @endif
                <dl class="space-y-3 p-4 text-sm">
                    <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                        <x-code-icon :name="$repository->isPublic() ? 'globe' : 'lock'" class="h-4 w-4 text-zinc-400 dark:text-zinc-500" />
                        <dt class="sr-only">Visibilidad</dt>
                        <dd>{{ $repository->isPublic() ? 'Público' : 'Privado' }}</dd>
                    </div>
                    <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                        <x-code-icon name="branch" class="h-4 w-4 text-zinc-400 dark:text-zinc-500" />
                        <dt>Rama por defecto:</dt>
                        <dd class="truncate font-mono text-zinc-800 dark:text-zinc-200">{{ $repository->default_branch }}</dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-code-icon name="branch" class="h-4 w-4 text-zinc-400 dark:text-zinc-500" />
                        <a href="{{ route('branches.index', $repository) }}" class="text-zinc-600 dark:text-zinc-400 hover:text-purple-600 dark:hover:text-purple-400 hover:underline">
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $branchesCount }}</span> {{ $branchesCount === 1 ? 'rama' : 'ramas' }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-code-icon name="history" class="h-4 w-4 text-zinc-400 dark:text-zinc-500" />
                        <a href="{{ route('commits.index', array_filter([$repository, 'branch' => $branchParam])) }}" class="text-zinc-600 dark:text-zinc-400 hover:text-purple-600 dark:hover:text-purple-400 hover:underline">
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $commitsCount }}</span> {{ $commitsCount === 1 ? 'commit' : 'commits' }}
                            @if ($branchName)<span class="text-xs text-zinc-500 dark:text-zinc-400">en {{ $branchName }}</span>@endif
                        </a>
                    </div>
                    <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                        <x-code-icon name="database" class="h-4 w-4 text-zinc-400 dark:text-zinc-500" />
                        <dt>Tamaño:</dt>
                        <dd class="text-zinc-800 dark:text-zinc-200"><x-code-size :bytes="$repository->size_bytes" /></dd>
                    </div>
                </dl>
            </x-card>
        </aside>
    </div>
</x-app-layout>
