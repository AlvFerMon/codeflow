{{--
    Visor de diferencias. files: resumen de VcsService::diff(); las entradas con 'hunks' se pintan
    ya abiertas y el resto se carga al desplegarlas desde $diffUrl?path=… (resources/js/diff.js).
--}}
@props(['files' => [], 'diffUrl' => null])
@php
    $statusLabel = ['added' => 'añadido', 'deleted' => 'eliminado', 'modified' => 'modificado'];
    $statusColor = ['added' => 'green', 'deleted' => 'red', 'modified' => 'amber'];
    $lazyCount = collect($files)->filter(fn ($f) => ! array_key_exists('hunks', $f))->count();
@endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ count($files) }} {{ count($files) === 1 ? 'archivo cambiado' : 'archivos cambiados' }} ·
            <span class="font-medium text-emerald-700 dark:text-emerald-300">+{{ collect($files)->sum('additions') }}</span>
            <span class="font-medium text-rose-700 dark:text-rose-300">−{{ collect($files)->sum('deletions') }}</span>
        </p>
        @if ($lazyCount > 0)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $lazyCount }} {{ $lazyCount === 1 ? 'archivo se carga' : 'archivos se cargan' }} al desplegar{{ $lazyCount === 1 ? 'lo' : 'los' }}.</p>
        @endif
    </div>
    @foreach ($files as $file)
        @php($eager = array_key_exists('hunks', $file))
        @php($src = $diffUrl ? $diffUrl.'?path='.rawurlencode($file['path']) : null)
        <details @if ($eager) open @endif @if (! $eager && $src) data-diff-src="{{ $src }}" @endif
            class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <summary class="flex cursor-pointer flex-wrap items-center gap-x-3 gap-y-1 border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-800/40">
                <x-badge :color="$statusColor[$file['status']] ?? 'slate'">{{ $statusLabel[$file['status']] ?? $file['status'] }}</x-badge>
                <span class="min-w-0 break-all font-mono text-zinc-800 dark:text-zinc-200">{{ $file['path'] }}</span>
                <span class="ml-auto font-mono text-xs">
                    <span class="text-emerald-700 dark:text-emerald-300">+{{ $file['additions'] }}</span>
                    <span class="text-rose-700 dark:text-rose-300">−{{ $file['deletions'] }}</span>
                </span>
            </summary>
            <div data-diff-body>
                @if ($eager)
                    <x-diff-body :file="$file" :full-url="$src ? $src.'&full=1' : null" />
                @else
                    <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                        @if ($file['binary'])
                            Archivo binario: no se muestran diferencias.
                        @else
                            <span data-diff-placeholder>Despliega el archivo para ver sus cambios.</span>
                            @if ($src)<a href="{{ $src }}&full=1" class="ml-1 text-purple-600 hover:underline dark:text-purple-400">Abrir aparte</a>@endif
                        @endif
                    </p>
                @endif
            </div>
        </details>
    @endforeach
</div>
