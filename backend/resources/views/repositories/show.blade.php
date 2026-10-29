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
        <div class="mb-4 flex flex-col gap-2 rounded-lg border-[1.5px] border-amber-500/60 bg-amber-500/10 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center dark:text-amber-200" role="status">
            <x-code-icon name="history" class="h-5 w-5" />
            <span>Estás viendo el código tal como estaba en el commit
                <a href="{{ route('commits.show', [$repository, $atCommit->sha]) }}" class="font-mono font-semibold underline">{{ substr($atCommit->sha, 0, 7) }}</a>
                ({{ $atCommit->created_at->format('d/m/Y H:i') }}). Es solo lectura.</span>
            <a href="{{ route('repositories.show', [$repository]) }}" class="font-semibold underline-offset-2 hover:underline sm:ml-auto">Volver a la versión actual →</a>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_17rem]">
        <div class="min-w-0 space-y-4">
            {{-- Barra de herramientas --}}
            <div class="flex flex-wrap items-center gap-3">
                @if ($branch && ! $atCommit)
                    <x-code-branch-select data-tour="selector-rama" :action="route('repositories.show', $repository)" :branches="$branches" :current="$branchName" :params="['path' => $path]" />
                @endif
                <x-code-breadcrumbs :repository="$repository" :branch="$branch" :commit="$atCommit" :path="$path" class="min-w-0 flex-1" />

                @if ($branch && ! $atCommit)
                    @can('push', $repository)
                        <div class="flex flex-wrap gap-2">
                            <x-button variant="secondary" class="py-1.5" data-tour="subir-archivos" :href="route('files.create', array_filter([$repository, 'mode' => 'upload', 'branch' => $branchName, 'path' => $path]))">
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
                    @php
                        $lastAt = $lastCommit->created_at;
                        $lastStamp = $lastAt->isToday() ? 'Hoy '.$lastAt->format('H:i') : ($lastAt->isYesterday() ? 'Ayer '.$lastAt->format('H:i') : $lastAt->translatedFormat('j M Y'));
                    @endphp
                    {{-- Último cambio como fila de la ficha: qué, quién y el sello de fecha --}}
                    <div class="border-b border-zinc-300 dark:border-zinc-800">
                        <div class="flex items-end justify-between gap-3 px-4 pt-2.5 text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <span>Último cambio · quién</span>
                            <a href="{{ route('commits.index', array_filter([$repository, 'branch' => $branchParam])) }}" class="inline-flex items-center gap-1 normal-case tracking-normal text-purple-600 hover:text-purple-700 dark:text-purple-300">
                                <x-code-icon name="history" /> Ver {{ $commitsCount === 1 ? 'el cambio' : 'los '.$commitsCount.' cambios' }}
                            </a>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 px-4 py-2.5 text-sm">
                            <a href="{{ route('commits.show', [$repository, $lastCommit->sha]) }}" class="min-w-0 flex-1 truncate font-bold text-zinc-900 hover:text-purple-600 dark:text-zinc-100 dark:hover:text-purple-300" title="{{ $lastCommit->message }}">
                                {{ \Illuminate\Support\Str::before((string) $lastCommit->message, "\n") }}
                                <span class="font-normal text-zinc-500 dark:text-zinc-400">· {{ $lastCommit->author?->name ?? 'Usuario eliminado' }}</span>
                            </a>
                            <a href="{{ route('commits.show', [$repository, $lastCommit->sha]) }}" class="font-mono text-xs text-zinc-600 hover:text-purple-600 dark:text-zinc-400 dark:hover:text-purple-300">{{ substr($lastCommit->sha, 0, 7) }}</a>
                            <time datetime="{{ $lastAt->toIso8601String() }}" title="{{ $lastAt->format('d/m/Y H:i') }} ({{ $lastAt->diffForHumans() }})" @class(['sello', 'sello-nuevo' => $lastAt->isToday()])>{{ $lastStamp }}</time>
                        </div>
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
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @if ($path !== '')
                                <tr class="hover:bg-purple-50/50 dark:hover:bg-purple-950/30">
                                    <td class="px-4 py-2" colspan="2">
                                        <a href="{{ route('repositories.show', array_filter([$repository, ...$refParams, 'path' => $parentPath])) }}" class="block font-mono text-zinc-500 dark:text-zinc-400 hover:text-purple-600 dark:hover:text-purple-400" title="Carpeta superior">..</a>
                                    </td>
                                </tr>
                            @endif
                            @forelse ($entries as $entry)
                                <tr class="hover:bg-purple-50/50 dark:hover:bg-purple-950/30">
                                    <td class="px-4 py-2">
                                        @if ($entry['type'] === 'dir')
                                            <a href="{{ route('repositories.show', array_filter([$repository, ...$refParams, 'path' => $entry['path']])) }}" class="flex items-center gap-2 text-zinc-800 dark:text-zinc-200 hover:text-purple-600 dark:hover:text-purple-400 hover:underline">
                                                <x-code-icon name="folder" class="h-4 w-4 text-purple-500 dark:text-purple-300" />
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
                    <p class="border-b border-zinc-200 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-800 dark:text-zinc-300">{{ $repository->description }}</p>
                @endif
                <dl class="divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Visibilidad</dt>
                        <dd><x-badge :variant="$repository->isPublic() ? 'success' : 'purple'" size="xs">{{ $repository->isPublic() ? 'Público' : 'Privado' }}</x-badge></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Rama principal</dt>
                        <dd class="truncate font-mono text-zinc-800 dark:text-zinc-200">{{ $repository->default_branch }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Ramas</dt>
                        <dd><a href="{{ route('branches.index', $repository) }}" class="font-mono font-bold text-zinc-800 hover:text-purple-600 dark:text-zinc-200 dark:hover:text-purple-300">{{ $branchesCount }}</a></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Cambios{{ $branchName ? ' en '.$branchName : '' }}</dt>
                        <dd><a href="{{ route('commits.index', array_filter([$repository, 'branch' => $branchParam])) }}" class="font-mono font-bold text-zinc-800 hover:text-purple-600 dark:text-zinc-200 dark:hover:text-purple-300">{{ $commitsCount }}</a></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tamaño</dt>
                        <dd class="font-mono text-zinc-800 dark:text-zinc-200"><x-code-size :bytes="$repository->size_bytes" /></dd>
                    </div>
                </dl>
            </x-card>
        </aside>
    </div>
</x-app-layout>
