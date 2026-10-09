{{-- Respuesta de commits.diff / pulls.diff: el cuerpo de un archivo. Con fetch se devuelve solo
     el fragmento; si se abre directamente («Abrir aparte») se muestra dentro de la página normal. --}}
@if (request()->ajax())
    <x-diff-body :file="$file" :full="$full" :full-url="$fullUrl" />
@else
    <x-app-layout :title="'Cambios en '.$file['path']">
        <a href="javascript:history.back()" class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">← Volver</a>
        <h1 class="mb-4 mt-1 break-all font-mono text-lg font-semibold text-zinc-900 dark:text-white">{{ $file['path'] }}</h1>
        <x-card class="overflow-hidden">
            <x-diff-body :file="$file" :full="true" />
        </x-card>
    </x-app-layout>
@endif
