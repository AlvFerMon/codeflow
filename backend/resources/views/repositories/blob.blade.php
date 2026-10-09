<x-app-layout :title="basename($path).' · '.$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'code'])

    @php
        $branchName = $branch->name;
        $branchParam = $branchName !== $repository->default_branch ? $branchName : null;
        $fileParams = array_filter([$repository, 'branch' => $branchParam, 'path' => $path]);
    @endphp

    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <x-code-branch-select :action="route('repositories.blob', $repository)" :branches="$branches" :current="$branchName" :params="['path' => $path]" />
            <x-code-breadcrumbs :repository="$repository" :branch="$branch" :path="$path" class="min-w-0 flex-1" />
        </div>

        <x-card class="overflow-hidden">
            {{-- Cabecera del archivo: tamaño, vista y acciones --}}
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 px-4 py-2 text-sm">
                <div class="flex items-center gap-3 text-xs text-zinc-600 dark:text-zinc-400">
                    @if (! $file['binary'] && ! $tooLarge)
                        <span>{{ count($lines) }} {{ count($lines) === 1 ? 'línea' : 'líneas' }}</span>
                        <span class="text-zinc-300 dark:text-zinc-600">·</span>
                    @endif
                    <x-code-size :bytes="$file['size']" />
                    @if ($file['binary'])
                        <x-badge>binario</x-badge>
                    @endif
                </div>

                @if ($isMarkdown && ! $file['binary'] && ! $tooLarge)
                    <div class="inline-flex overflow-hidden rounded-lg border border-zinc-300 dark:border-zinc-700 text-xs font-medium">
                        <a href="{{ route('repositories.blob', $fileParams) }}" @class(['flex items-center gap-1 px-2.5 py-1', 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100' => ! $rendered, 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-white dark:hover:bg-zinc-900' => $rendered])><x-code-icon name="code" class="h-3.5 w-3.5" /> Código</a>
                        <a href="{{ route('repositories.blob', $fileParams + ['view' => 'rendered']) }}" @class(['flex items-center gap-1 border-l border-zinc-300 dark:border-zinc-700 px-2.5 py-1', 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100' => $rendered, 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-white dark:hover:bg-zinc-900' => ! $rendered])><x-code-icon name="eye" class="h-3.5 w-3.5" /> Vista previa</a>
                    </div>
                @endif

                <div class="ml-auto flex flex-wrap items-center gap-1">
                    <x-button variant="ghost" class="px-2.5 py-1 text-xs" :href="route('commits.index', $fileParams)" title="Commits que cambiaron este archivo">
                        <x-code-icon name="history" /> Historial
                    </x-button>
                    <x-button variant="ghost" class="px-2.5 py-1 text-xs" :href="route('repositories.raw', $fileParams)" target="_blank" rel="noopener">Raw</x-button>
                    <x-button variant="ghost" class="px-2.5 py-1 text-xs" :href="route('repositories.raw', $fileParams + ['download' => 1])">
                        <x-code-icon name="download" /> Descargar
                    </x-button>
                    @can('push', $repository)
                        @unless ($file['binary'])
                            <x-button variant="ghost" class="px-2.5 py-1 text-xs" :href="route('files.edit', [$repository, 'branch' => $branchName, 'path' => $path])">
                                <x-code-icon name="pencil" /> Editar
                            </x-button>
                        @endunless
                        <x-button variant="ghost" type="button" class="px-2.5 py-1 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10"
                            onclick="document.getElementById('delete-file').toggleAttribute('open')">
                            <x-code-icon name="trash" /> Eliminar
                        </x-button>
                    @endcan
                </div>
            </div>

            @can('push', $repository)
                <details id="delete-file" class="border-b border-rose-200 dark:border-rose-500/30 bg-rose-50/50 dark:bg-rose-500/10 [&:not([open])]:hidden">
                    <summary class="sr-only">Eliminar archivo</summary>
                    <form method="POST" action="{{ route('files.destroy', $repository) }}" class="space-y-3 p-4"
                        data-confirm="¿Eliminar {{ $path }}?" onsubmit="return confirm(this.dataset.confirm)">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="branch" value="{{ $branchName }}">
                        <input type="hidden" name="path" value="{{ $path }}">
                        <p class="text-sm text-zinc-700 dark:text-zinc-300">Se creará un commit en <span class="font-mono">{{ $branchName }}</span> que elimina <span class="font-mono">{{ $path }}</span>. Las versiones anteriores siguen en el historial.</p>
                        <x-input name="message" label="Mensaje del commit" :placeholder="'Eliminar '.$path" maxlength="500" />
                        <div class="flex gap-2">
                            <x-button variant="danger"><x-code-icon name="trash" /> Eliminar archivo</x-button>
                            <x-button variant="secondary" type="button" onclick="document.getElementById('delete-file').removeAttribute('open')">Cancelar</x-button>
                        </div>
                    </form>
                </details>
            @endcan

            {{-- Contenido --}}
            @if ($file['binary'])
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Archivo binario</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No se puede mostrar en el navegador.</p>
                    <x-button class="mt-4" :href="route('repositories.raw', $fileParams + ['download' => 1])"><x-code-icon name="download" /> Descargar ({{ basename($path) }})</x-button>
                </div>
            @elseif ($tooLarge)
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">El archivo es demasiado grande para mostrarse</p>
                    <x-button class="mt-4" variant="secondary" :href="route('repositories.raw', $fileParams)">Ver en bruto</x-button>
                </div>
            @elseif ($rendered)
                <div class="markdown p-4 sm:p-6">{!! \Illuminate\Support\Str::markdown($file['content'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
            @elseif (empty($lines))
                <p class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">El archivo está vacío.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse font-mono text-xs leading-5">
                        <tbody>
                            @foreach ($lines as $i => $line)
                                <tr id="L{{ $i + 1 }}" class="hover:bg-amber-50/60 dark:hover:bg-amber-500/10 target:bg-amber-100 dark:target:bg-amber-500/20">
                                    <td class="w-1 select-none whitespace-nowrap border-r border-zinc-200 dark:border-zinc-800 px-3 text-right align-top text-zinc-400 dark:text-zinc-500">
                                        <a href="#L{{ $i + 1 }}" class="hover:text-zinc-700 dark:hover:text-zinc-300">{{ $i + 1 }}</a>
                                    </td>
                                    <td class="whitespace-pre px-4 text-zinc-800 dark:text-zinc-200">{{ $line }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
