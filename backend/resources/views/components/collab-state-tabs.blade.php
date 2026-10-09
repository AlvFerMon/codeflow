{{-- Pestañas Abiertas/Cerradas con recuento. Props: state, counts [open, closed], labels [open, closed], route, repository --}}
@props(['state', 'counts', 'labels', 'route', 'repository'])
<div class="flex items-center gap-1 text-sm">
    @foreach (['open', 'closed'] as $key)
        <a href="{{ route($route, [$repository, 'state' => $key] + request()->except(['state', 'status', 'page'])) }}" @class([
            'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5',
            'bg-zinc-900 font-medium text-white' => $state === $key,
            'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' => $state !== $key,
        ])>
            {{ $labels[$key] }}
            <span @class(['rounded-full px-1.5 text-xs', 'bg-white/20 dark:bg-zinc-900/20' => $state === $key, 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' => $state !== $key])>{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>
