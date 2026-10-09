{{--
    Visor de diferencias. Recibe la salida de VcsService::diff(..., withHunks: true):
    [ ['path','status','additions','deletions','binary','too_large','hunks' => [...]], ... ]
--}}
@props(['files' => []])
@php
    $statusLabel = ['added' => 'añadido', 'deleted' => 'eliminado', 'modified' => 'modificado'];
    $statusColor = ['added' => 'green', 'deleted' => 'red', 'modified' => 'amber'];
@endphp
<div class="space-y-4">
    <p class="text-sm text-zinc-600 dark:text-zinc-400">
        {{ count($files) }} {{ count($files) === 1 ? 'archivo cambiado' : 'archivos cambiados' }} ·
        <span class="font-medium text-emerald-700 dark:text-emerald-300">+{{ collect($files)->sum('additions') }}</span>
        <span class="font-medium text-rose-700 dark:text-rose-300">−{{ collect($files)->sum('deletions') }}</span>
    </p>
    @foreach ($files as $file)
        <details open class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <summary class="flex cursor-pointer flex-wrap items-center gap-x-3 gap-y-1 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 px-4 py-2 text-sm">
                <x-badge :color="$statusColor[$file['status']] ?? 'slate'">{{ $statusLabel[$file['status']] ?? $file['status'] }}</x-badge>
                <span class="min-w-0 break-all font-mono text-zinc-800 dark:text-zinc-200">{{ $file['path'] }}</span>
                <span class="ml-auto font-mono text-xs">
                    <span class="text-emerald-700 dark:text-emerald-300">+{{ $file['additions'] }}</span>
                    <span class="text-rose-700 dark:text-rose-300">−{{ $file['deletions'] }}</span>
                </span>
            </summary>
            @if (! empty($file['binary']))
                <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">Archivo binario: no se muestran diferencias.</p>
            @elseif (! empty($file['too_large']))
                <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">El diff es demasiado grande para mostrarse.</p>
            @elseif (empty($file['hunks']))
                <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">Sin cambios de contenido visibles.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse font-mono text-xs leading-5">
                        @foreach ($file['hunks'] as $hunk)
                            <tr class="bg-sky-50 dark:bg-sky-500/10 text-sky-800 dark:text-sky-300">
                                <td colspan="3" class="px-3 py-1">@@ -{{ $hunk['old_start'] }},{{ $hunk['old_lines'] }} +{{ $hunk['new_start'] }},{{ $hunk['new_lines'] }} @@</td>
                            </tr>
                            @foreach ($hunk['lines'] as $line)
                                <tr @class([
                                    'bg-emerald-50 dark:bg-emerald-500/10' => $line['type'] === 'add',
                                    'bg-rose-50 dark:bg-rose-500/10' => $line['type'] === 'del',
                                ])>
                                    <td class="w-12 select-none border-r border-zinc-200 dark:border-zinc-800 px-2 text-right text-zinc-400 dark:text-zinc-500">{{ $line['old'] }}</td>
                                    <td class="w-12 select-none border-r border-zinc-200 dark:border-zinc-800 px-2 text-right text-zinc-400 dark:text-zinc-500">{{ $line['new'] }}</td>
                                    <td class="whitespace-pre px-3 @if($line['type'] === 'add') text-emerald-900 dark:text-emerald-300 @elseif($line['type'] === 'del') text-rose-900 @endif">{{ $line['type'] === 'add' ? '+' : ($line['type'] === 'del' ? '-' : ' ') }}{{ $line['text'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </table>
                </div>
            @endif
        </details>
    @endforeach
</div>
