<x-app-layout :title="$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'code'])

    @php
        $branchName = $branch?->name;
        $branchParam = $branchName && $branchName !== $repository->default_branch ? $branchName : null;
        $parentPath = str_contains($path, '/') ? dirname($path) : '';
    @endphp

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_16rem]">
        <div class="min-w-0 space-y-4">
            {{-- Barra de herramientas --}}
            <div class="flex flex-wrap items-center gap-3">
                @if ($branch)
                    <x-code-branch-select :action="route('repositories.show', $repository)" :branches="$branches" :current="$branchName" :params="['path' => $path]" />
                @endif
                <x-code-breadcrumbs :repository="$repository" :branch="$branch" :path="$path" class="min-w-0 flex-1" />

                @if ($branch)
                    @can('push', $repository)
                        <div class="flex flex-wrap gap-2">
                            <x-button variant="secondary" class="py-1.5" :href="route('files.create', array_filter([$repository, 'mode' => 'upload', 'branch' => $branchName, 'path' => $path]))">
                                <x-code-icon name="upload" /> Subir archivos
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
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <x-code-avatar :user="$lastCommit->author" />
                        <span class="font-medium text-slate-800">{{ $lastCommit->author?->name ?? 'Usuario eliminado' }}</span>
                        <a href="{{ route('commits.show', [$repository, $lastCommit->sha]) }}" class="min-w-0 flex-1 truncate text-slate-600 hover:text-indigo-600 hover:underline" title="{{ $lastCommit->message }}">{{ \Illuminate\Support\Str::before((string) $lastCommit->message, "\n") }}</a>
                        <span class="flex items-center gap-3 text-xs text-slate-500">
                            <a href="{{ route('commits.show', [$repository, $lastCommit->sha]) }}" class="font-mono text-slate-600 hover:text-indigo-600 hover:underline">{{ substr($lastCommit->sha, 0, 7) }}</a>
                            <time datetime="{{ $lastCommit->created_at->toIso8601String() }}" title="{{ $lastCommit->created_at->format('d/m/Y H:i') }}">{{ $lastCommit->created_at->diffForHumans() }}</time>
                            <a href="{{ route('commits.index', array_filter([$repository, 'branch' => $branchParam])) }}" class="inline-flex items-center gap-1 font-medium text-slate-700 hover:text-indigo-600">
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
                                    <x-button :href="route('files.create', array_filter([$repository, 'mode' => 'upload', 'branch' => $branchName]))"><x-code-icon name="upload" /> Subir archivos</x-button>
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
                        <tbody class="divide-y divide-slate-100">
                            @if ($path !== '')
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2" colspan="2">
                                        <a href="{{ route('repositories.show', array_filter([$repository, 'branch' => $branchParam, 'path' => $parentPath])) }}" class="block font-mono text-slate-500 hover:text-indigo-600" title="Carpeta superior">..</a>
                                    </td>
                                </tr>
                            @endif
                            @forelse ($entries as $entry)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2">
                                        @if ($entry['type'] === 'dir')
                                            <a href="{{ route('repositories.show', array_filter([$repository, 'branch' => $branchParam, 'path' => $entry['path']])) }}" class="flex items-center gap-2 text-slate-800 hover:text-indigo-600 hover:underline">
                                                <x-code-icon name="folder" class="h-4 w-4 text-sky-500" />
                                                <span class="break-all">{{ $entry['name'] }}</span>
                                            </a>
                                        @else
                                            <a href="{{ route('repositories.blob', array_filter([$repository, 'branch' => $branchParam, 'path' => $entry['path']])) }}" class="flex items-center gap-2 text-slate-800 hover:text-indigo-600 hover:underline">
                                                <x-code-icon name="file" class="h-4 w-4 text-slate-400" />
                                                <span class="break-all">{{ $entry['name'] }}</span>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-xs text-slate-500">
                                        @if ($entry['type'] === 'file')
                                            <x-code-size :bytes="$sizes[$entry['blob_id']] ?? 0" />
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-sm text-slate-500" colspan="2">Esta carpeta está vacía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </x-card>

            @if ($path !== '' && $branch)
                @can('push', $repository)
                    <details class="rounded-lg border border-rose-200 bg-white">
                        <summary class="cursor-pointer px-4 py-2 text-sm font-medium text-rose-700">Eliminar esta carpeta</summary>
                        <form method="POST" action="{{ route('files.destroy', $repository) }}" class="space-y-3 border-t border-rose-100 p-4"
                            data-confirm="¿Eliminar la carpeta {{ $path }} y todo su contenido?" onsubmit="return confirm(this.dataset.confirm)">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="branch" value="{{ $branchName }}">
                            <input type="hidden" name="path" value="{{ $path }}">
                            <p class="text-sm text-slate-600">Se eliminarán todos los archivos de <span class="font-mono">{{ $path }}</span> en la rama <span class="font-mono">{{ $branchName }}</span> con un nuevo commit. El historial anterior se conserva.</p>
                            <x-input name="message" label="Mensaje del commit" :placeholder="'Eliminar carpeta '.$path" maxlength="500" />
                            <x-button variant="danger"><x-code-icon name="trash" /> Eliminar carpeta</x-button>
                        </form>
                    </details>
                @endcan
            @endif

            @if ($readme)
                <x-card :title="$readme['name']">
                    <x-slot:actions>
                        <a href="{{ route('repositories.blob', array_filter([$repository, 'branch' => $branchParam, 'path' => $readme['path']])) }}" class="text-xs text-indigo-600 hover:underline">Ver archivo</a>
                    </x-slot:actions>
                    <div class="p-4 sm:p-6">
                        @if ($readme['markdown'])
                            <div class="markdown">{!! \Illuminate\Support\Str::markdown($readme['content'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                        @else
                            <pre class="overflow-x-auto whitespace-pre-wrap font-mono text-sm text-slate-700">{{ $readme['content'] }}</pre>
                        @endif
                    </div>
                </x-card>
            @endif
        </div>

        {{-- Información del repositorio --}}
        <aside class="space-y-4">
            <x-card title="Acerca de">
                @if ($repository->description)
                    <p class="px-4 pt-4 text-sm text-slate-600">{{ $repository->description }}</p>
                @endif
                <dl class="space-y-3 p-4 text-sm">
                    <div class="flex items-center gap-2 text-slate-600">
                        <x-code-icon :name="$repository->isPublic() ? 'globe' : 'lock'" class="h-4 w-4 text-slate-400" />
                        <dt class="sr-only">Visibilidad</dt>
                        <dd>{{ $repository->isPublic() ? 'Público' : 'Privado' }}</dd>
                    </div>
                    <div class="flex items-center gap-2 text-slate-600">
                        <x-code-icon name="branch" class="h-4 w-4 text-slate-400" />
                        <dt>Rama por defecto:</dt>
                        <dd class="truncate font-mono text-slate-800">{{ $repository->default_branch }}</dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-code-icon name="branch" class="h-4 w-4 text-slate-400" />
                        <a href="{{ route('branches.index', $repository) }}" class="text-slate-600 hover:text-indigo-600 hover:underline">
                            <span class="font-semibold text-slate-800">{{ $branchesCount }}</span> {{ $branchesCount === 1 ? 'rama' : 'ramas' }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-code-icon name="history" class="h-4 w-4 text-slate-400" />
                        <a href="{{ route('commits.index', array_filter([$repository, 'branch' => $branchParam])) }}" class="text-slate-600 hover:text-indigo-600 hover:underline">
                            <span class="font-semibold text-slate-800">{{ $commitsCount }}</span> {{ $commitsCount === 1 ? 'commit' : 'commits' }}
                            @if ($branchName)<span class="text-xs text-slate-500">en {{ $branchName }}</span>@endif
                        </a>
                    </div>
                    <div class="flex items-center gap-2 text-slate-600">
                        <x-code-icon name="database" class="h-4 w-4 text-slate-400" />
                        <dt>Tamaño:</dt>
                        <dd class="text-slate-800"><x-code-size :bytes="$repository->size_bytes" /></dd>
                    </div>
                </dl>
            </x-card>
        </aside>
    </div>
</x-app-layout>
