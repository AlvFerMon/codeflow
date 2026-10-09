{{-- Líneas de un archivo del diff. Props: file (con hunks), full (sin límite de líneas), fullUrl (fragmento completo). --}}
@props(['file', 'full' => false, 'fullUrl' => null])
@php
    $limit = $full ? PHP_INT_MAX : \App\Support\DiffSelection::LINES_PER_FILE;
    $total = collect($file['hunks'] ?? [])->sum(fn ($h) => count($h['lines']) + 1);
    $shown = 0;
@endphp
@if (! empty($file['binary']))
    <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">Archivo binario: no se muestran diferencias.</p>
@elseif (! empty($file['too_large']))
    <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">El archivo es demasiado grande para comparar línea a línea (+{{ $file['additions'] }} −{{ $file['deletions'] }} aproximadamente).</p>
@elseif (empty($file['hunks']))
    <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">Sin cambios de contenido visibles.</p>
@else
    <div class="overflow-x-auto">
        <table class="w-full border-collapse font-mono text-xs leading-5">
            @foreach ($file['hunks'] as $hunk)
                @break($shown >= $limit)
                @php($shown++)
                <tr class="bg-sky-50 text-sky-800 dark:bg-sky-500/10 dark:text-sky-300">
                    <td colspan="3" class="px-3 py-1">@@ -{{ $hunk['old_start'] }},{{ $hunk['old_lines'] }} +{{ $hunk['new_start'] }},{{ $hunk['new_lines'] }} @@</td>
                </tr>
                @foreach ($hunk['lines'] as $line)
                    @break($shown >= $limit)
                    @php($shown++)
                    <tr @class(['bg-emerald-50 dark:bg-emerald-500/10' => $line['type'] === 'add', 'bg-rose-50 dark:bg-rose-500/10' => $line['type'] === 'del'])>
                        <td class="w-12 select-none border-r border-zinc-200 px-2 text-right text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">{{ $line['old'] }}</td>
                        <td class="w-12 select-none border-r border-zinc-200 px-2 text-right text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">{{ $line['new'] }}</td>
                        <td @class(['whitespace-pre px-3', 'text-emerald-900 dark:text-emerald-300' => $line['type'] === 'add', 'text-rose-900 dark:text-rose-300' => $line['type'] === 'del', 'text-zinc-800 dark:text-zinc-200' => $line['type'] === 'ctx'])>{{ $line['type'] === 'add' ? '+' : ($line['type'] === 'del' ? '-' : ' ') }}{{ $line['text'] }}</td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    </div>
    @if ($total > $shown && $fullUrl)
        <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-2 text-center text-xs dark:border-zinc-800 dark:bg-zinc-800/40">
            <a href="{{ $fullUrl }}" data-diff-more class="font-semibold text-purple-600 hover:underline dark:text-purple-400">Mostrar las {{ $total - $shown }} líneas restantes</a>
        </div>
    @endif
@endif
