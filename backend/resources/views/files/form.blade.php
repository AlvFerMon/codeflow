@php
    $titles = [
        'upload' => 'Subir archivos o carpetas',
        'new' => 'Nuevo archivo',
        'folder' => 'Nueva carpeta',
        'edit' => 'Editar archivo',
    ];
    $title = $titles[$mode];
    $branchParam = $branch->name !== $repository->default_branch ? $branch->name : null;
    $cancelUrl = $mode === 'edit'
        ? route('repositories.blob', array_filter([$repository, 'branch' => $branchParam, 'path' => $path]))
        : route('repositories.show', array_filter([$repository, 'branch' => $branchParam, 'path' => $directory]));
    $defaultMessage = match ($mode) {
        'upload' => 'Subir archivos',
        'new' => 'Crear archivo',
        'folder' => 'Crear carpeta',
        'edit' => 'Actualizar '.$path,
    };
@endphp
<x-app-layout :title="$title.' · '.$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'code'])

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h1>
        <span class="inline-flex items-center gap-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 font-mono text-xs text-zinc-700 dark:text-zinc-300">
            <x-code-icon name="branch" class="h-3.5 w-3.5" /> {{ $branch->name }}
        </span>
        @if ($mode !== 'edit')
            <nav class="ml-auto flex overflow-hidden rounded-lg border border-zinc-300 dark:border-zinc-700 text-xs font-medium">
                @foreach (['upload' => 'Subir', 'new' => 'Archivo', 'folder' => 'Carpeta'] as $key => $label)
                    <a href="{{ route('files.create', array_filter([$repository, 'mode' => $key, 'branch' => $branch->name, 'path' => $directory])) }}"
                        @class(['px-3 py-1.5', 'border-l border-zinc-300 dark:border-zinc-700' => ! $loop->first, 'bg-purple-600 text-white' => $mode === $key, 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/40' => $mode !== $key])>{{ $label }}</a>
                @endforeach
            </nav>
        @endif
    </div>

    <x-help summary="¿Qué pasa al confirmar los cambios?" class="-mt-2 mb-4">
        Se crea un <strong>commit</strong> en la rama <span class="font-mono">{{ $branch->name }}</span> con lo que subas o edites, y queda en el historial
        con tu nombre y el mensaje que escribas. Las versiones anteriores no se pierden: siempre puedes consultarlas en «Commits».
    </x-help>

    <form method="POST" action="{{ route('files.store', $repository) }}" @if ($mode === 'upload') enctype="multipart/form-data" @endif class="space-y-4">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">
        <input type="hidden" name="branch" value="{{ $branch->name }}">

        <x-card>
            <div class="space-y-4 p-4 sm:p-6">
                @if ($mode === 'upload')
                    <x-input name="directory" label="Carpeta de destino" :value="$directory" placeholder="(raíz del repositorio)" class="font-mono"
                        help="Deja el campo vacío para subirlos a la raíz. Si la carpeta no existe se creará." />
                    <div data-upload
                        data-max-files="{{ $limits['files'] }}"
                        data-max-file-bytes="{{ $limits['upload_bytes'] }}"
                        data-max-total-bytes="{{ $limits['total_bytes'] }}">
                        <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Archivos y carpetas</span>
                        <div data-upload-drop class="rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 px-4 py-8 text-center transition">
                            <x-code-icon name="folder" class="mx-auto h-8 w-8 text-zinc-400 dark:text-zinc-500" />
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Arrastra aquí archivos o carpetas completas</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">o</p>
                            <div class="mt-2 flex flex-wrap justify-center gap-2">
                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-purple-500">
                                    <x-code-icon name="file" class="h-4 w-4" /> Seleccionar archivos
                                    <input type="file" name="files[]" multiple data-upload-files class="sr-only">
                                </label>
                                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                    <x-code-icon name="folder" class="h-4 w-4" /> Seleccionar carpeta
                                    <input type="file" webkitdirectory directory multiple data-upload-folder class="sr-only">
                                </label>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <p data-upload-summary class="text-sm text-zinc-600 dark:text-zinc-400">Ningún archivo seleccionado.</p>
                            <button type="button" data-upload-clear class="text-xs text-zinc-500 dark:text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400 hover:underline">Vaciar lista</button>
                        </div>
                        <ul data-upload-problems hidden class="mt-2 list-inside list-disc rounded-lg border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 px-3 py-2 text-sm text-rose-700 dark:text-rose-300"></ul>
                        <ul data-upload-list hidden class="mt-2 max-h-72 divide-y divide-zinc-100 dark:divide-zinc-800 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900"></ul>

                        <div data-upload-progress hidden class="mt-3">
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div data-upload-bar class="h-full w-0 rounded-full bg-purple-600 transition-all"></div>
                            </div>
                            <p data-upload-progress-text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Preparando…</p>
                        </div>

                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            Hasta {{ $limits['files'] }} archivos y {{ $limits['total_label'] }} por subida, {{ $limits['upload_label'] }} por archivo.
                            Al subir una carpeta se conserva su estructura. Se omiten <code class="font-mono">.git</code>,
                            <code class="font-mono">node_modules</code> y archivos del sistema. Si un archivo ya existe se reemplaza por la versión nueva.
                        </p>
                        @error('files')<span class="mt-1 block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span>@enderror
                    </div>
                @elseif ($mode === 'folder')
                    <div class="grid gap-2 sm:grid-cols-[auto_1fr] sm:items-end">
                        <span class="pb-2 font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $repository->name }}/{{ $directory !== '' ? $directory.'/' : '' }}</span>
                        <x-input name="folder" label="Nombre de la carpeta" required maxlength="200" placeholder="docs" class="font-mono" autofocus />
                    </div>
                    <input type="hidden" name="directory" value="{{ $directory }}">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Las carpetas no existen por sí solas: se crea un archivo vacío <code class="font-mono">.keep</code> dentro (no se muestra en el explorador).</p>
                @else
                    <div class="grid gap-2 sm:grid-cols-[minmax(0,16rem)_1fr]">
                        <x-input name="directory" label="Carpeta" :value="$directory" placeholder="(raíz)" class="font-mono" />
                        <x-input name="filename" label="Nombre del archivo" :value="$mode === 'edit' ? basename($path) : null" required maxlength="255" placeholder="ejemplo.php" class="font-mono" :autofocus="$mode === 'new'" />
                    </div>
                    @if ($mode === 'edit')
                        <input type="hidden" name="original_path" value="{{ $path }}">
                        <input type="hidden" name="original_blob" value="{{ $blobId }}">
                        <p class="-mt-2 text-xs text-zinc-500 dark:text-zinc-400">Si cambias la carpeta o el nombre, el archivo se moverá en el mismo commit.</p>
                    @endif
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Contenido</span>
                        <textarea name="file_content" rows="22" spellcheck="false" wrap="off"
                            class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 px-3 py-2 font-mono text-xs leading-5 text-zinc-800 dark:text-zinc-200 shadow-sm focus:border-purple-500 focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:ring-1 focus:ring-purple-500 @error('file_content') border-rose-400 @enderror"
                            style="tab-size: 4">{{-- El salto de línea siguiente lo descarta el navegador: así se conserva una línea vacía inicial --}}
{{ old('file_content', $content ?? '') }}</textarea>
                        <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">Máximo {{ $limits['label'] }}.</span>
                        @error('file_content')<span class="mt-1 block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span>@enderror
                    </label>
                @endif
            </div>
        </x-card>

        <x-card title="Confirmar cambios">
            <div class="space-y-4 p-4 sm:p-6">
                <x-input name="message" label="Mensaje del commit" :placeholder="$defaultMessage" maxlength="500"
                    help="Describe brevemente el cambio. Si lo dejas vacío se usará el texto de ejemplo." />
                <p class="text-xs text-zinc-500 dark:text-zinc-400">El commit se creará directamente en la rama <span class="font-mono font-medium text-zinc-700 dark:text-zinc-300">{{ $branch->name }}</span>.</p>
                <div class="flex flex-wrap justify-end gap-2">
                    <x-button variant="secondary" :href="$cancelUrl">Cancelar</x-button>
                    <x-button variant="success">Confirmar cambios</x-button>
                </div>
            </div>
        </x-card>
    </form>
</x-app-layout>
