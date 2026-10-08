{{-- Pestañas Abiertas/Cerradas con recuento. Props: state, counts [open, closed], labels [open, closed], route, repository --}}
@props(['state', 'counts', 'labels', 'route', 'repository'])
<div class="flex items-center gap-1 text-sm">
    @foreach (['open', 'closed'] as $key)
        <a href="{{ route($route, [$repository, 'state' => $key] + request()->except(['state', 'status', 'page'])) }}" @class([
            'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5',
            'bg-slate-900 font-medium text-white' => $state === $key,
            'text-slate-600 hover:bg-slate-100' => $state !== $key,
        ])>
            {{ $labels[$key] }}
            <span @class(['rounded-full px-1.5 text-xs', 'bg-white/20' => $state === $key, 'bg-slate-200 text-slate-700' => $state !== $key])>{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>
