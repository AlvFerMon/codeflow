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
    <p class="text-sm text-slate-600">
        {{ count($files) }} {{ count($files) === 1 ? 'archivo cambiado' : 'archivos cambiados' }} ·
        <span class="font-medium text-emerald-700">+{{ collect($files)->sum('additions') }}</span>
        <span class="font-medium text-rose-700">−{{ collect($files)->sum('deletions') }}</span>
    </p>
    @foreach ($files as $file)
        <details open class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <summary class="flex cursor-pointer flex-wrap items-center gap-x-3 gap-y-1 border-b border-slate-200 bg-slate-50 px-4 py-2 text-sm">
                <x-badge :color="$statusColor[$file['status']] ?? 'slate'">{{ $statusLabel[$file['status']] ?? $file['status'] }}</x-badge>
                <span class="min-w-0 break-all font-mono text-slate-800">{{ $file['path'] }}</span>
                <span class="ml-auto font-mono text-xs">
                    <span class="text-emerald-700">+{{ $file['additions'] }}</span>
                    <span class="text-rose-700">−{{ $file['deletions'] }}</span>
                </span>
            </summary>
            @if (! empty($file['binary']))
                <p class="px-4 py-3 text-sm text-slate-500">Archivo binario: no se muestran diferencias.</p>
            @elseif (! empty($file['too_large']))
                <p class="px-4 py-3 text-sm text-slate-500">El diff es demasiado grande para mostrarse.</p>
            @elseif (empty($file['hunks']))
                <p class="px-4 py-3 text-sm text-slate-500">Sin cambios de contenido visibles.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse font-mono text-xs leading-5">
                        @foreach ($file['hunks'] as $hunk)
                            <tr class="bg-sky-50 text-sky-800">
                                <td colspan="3" class="px-3 py-1">@@ -{{ $hunk['old_start'] }},{{ $hunk['old_lines'] }} +{{ $hunk['new_start'] }},{{ $hunk['new_lines'] }} @@</td>
                            </tr>
                            @foreach ($hunk['lines'] as $line)
                                <tr @class([
                                    'bg-emerald-50' => $line['type'] === 'add',
                                    'bg-rose-50' => $line['type'] === 'del',
                                ])>
                                    <td class="w-12 select-none border-r border-slate-200 px-2 text-right text-slate-400">{{ $line['old'] }}</td>
                                    <td class="w-12 select-none border-r border-slate-200 px-2 text-right text-slate-400">{{ $line['new'] }}</td>
                                    <td class="whitespace-pre px-3 @if($line['type'] === 'add') text-emerald-900 @elseif($line['type'] === 'del') text-rose-900 @endif">{{ $line['type'] === 'add' ? '+' : ($line['type'] === 'del' ? '-' : ' ') }}{{ $line['text'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </table>
                </div>
            @endif
        </details>
    @endforeach
</div>
