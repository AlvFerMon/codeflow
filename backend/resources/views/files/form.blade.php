@php
    $titles = [
        'upload' => 'Subir archivos',
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
        <h1 class="text-lg font-semibold text-slate-900">{{ $title }}</h1>
        <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">
            <x-code-icon name="branch" class="h-3.5 w-3.5" /> {{ $branch->name }}
        </span>
        @if ($mode !== 'edit')
            <nav class="ml-auto flex overflow-hidden rounded-md border border-slate-300 text-xs font-medium">
                @foreach (['upload' => 'Subir', 'new' => 'Archivo', 'folder' => 'Carpeta'] as $key => $label)
                    <a href="{{ route('files.create', array_filter([$repository, 'mode' => $key, 'branch' => $branch->name, 'path' => $directory])) }}"
                        @class(['px-3 py-1.5', 'border-l border-slate-300' => ! $loop->first, 'bg-indigo-600 text-white' => $mode === $key, 'bg-white text-slate-600 hover:bg-slate-50' => $mode !== $key])>{{ $label }}</a>
                @endforeach
            </nav>
        @endif
    </div>

    <form method="POST" action="{{ route('files.store', $repository) }}" @if ($mode === 'upload') enctype="multipart/form-data" @endif class="space-y-4">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">
        <input type="hidden" name="branch" value="{{ $branch->name }}">

        <x-card>
            <div class="space-y-4 p-4 sm:p-6">
                @if ($mode === 'upload')
                    <x-input name="directory" label="Carpeta de destino" :value="$directory" placeholder="(raíz del repositorio)" class="font-mono"
                        help="Deja el campo vacío para subirlos a la raíz. Si la carpeta no existe se creará." />
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-slate-700">Archivos</span>
                        <input type="file" name="files[]" multiple required
                            class="block w-full cursor-pointer rounded-md border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white hover:bg-slate-100">
                        <span class="mt-1 block text-xs text-slate-500">
                            Hasta {{ $limits['files'] }} archivos por subida y {{ $limits['upload_label'] }} por archivo.
                            Si ya existe un archivo con el mismo nombre se reemplaza por la versión nueva.
                        </span>
                        @error('files')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                    </label>
                @elseif ($mode === 'folder')
                    <div class="grid gap-2 sm:grid-cols-[auto_1fr] sm:items-end">
                        <span class="pb-2 font-mono text-sm text-slate-500">{{ $repository->name }}/{{ $directory !== '' ? $directory.'/' : '' }}</span>
                        <x-input name="folder" label="Nombre de la carpeta" required maxlength="200" placeholder="docs" class="font-mono" autofocus />
                    </div>
                    <input type="hidden" name="directory" value="{{ $directory }}">
                    <p class="text-xs text-slate-500">Las carpetas no existen por sí solas: se crea un archivo vacío <code class="font-mono">.keep</code> dentro (no se muestra en el explorador).</p>
                @else
                    <div class="grid gap-2 sm:grid-cols-[minmax(0,16rem)_1fr]">
                        <x-input name="directory" label="Carpeta" :value="$directory" placeholder="(raíz)" class="font-mono" />
                        <x-input name="filename" label="Nombre del archivo" :value="$mode === 'edit' ? basename($path) : null" required maxlength="255" placeholder="ejemplo.php" class="font-mono" :autofocus="$mode === 'new'" />
                    </div>
                    @if ($mode === 'edit')
                        <input type="hidden" name="original_path" value="{{ $path }}">
                        <input type="hidden" name="original_blob" value="{{ $blobId }}">
                        <p class="-mt-2 text-xs text-slate-500">Si cambias la carpeta o el nombre, el archivo se moverá en el mismo commit.</p>
                    @endif
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-slate-700">Contenido</span>
                        <textarea name="file_content" rows="22" spellcheck="false" wrap="off"
                            class="block w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 font-mono text-xs leading-5 text-slate-800 shadow-sm focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 @error('file_content') border-rose-400 @enderror"
                            style="tab-size: 4">{{-- El salto de línea siguiente lo descarta el navegador: así se conserva una línea vacía inicial --}}
{{ old('file_content', $content ?? '') }}</textarea>
                        <span class="mt-1 block text-xs text-slate-500">Máximo {{ $limits['label'] }}.</span>
                        @error('file_content')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                    </label>
                @endif
            </div>
        </x-card>

        <x-card title="Confirmar cambios">
            <div class="space-y-4 p-4 sm:p-6">
                <x-input name="message" label="Mensaje del commit" :placeholder="$defaultMessage" maxlength="500"
                    help="Describe brevemente el cambio. Si lo dejas vacío se usará el texto de ejemplo." />
                <p class="text-xs text-slate-500">El commit se creará directamente en la rama <span class="font-mono font-medium text-slate-700">{{ $branch->name }}</span>.</p>
                <div class="flex flex-wrap justify-end gap-2">
                    <x-button variant="secondary" :href="$cancelUrl">Cancelar</x-button>
                    <x-button variant="success">Confirmar cambios</x-button>
                </div>
            </div>
        </x-card>
    </form>
</x-app-layout>
