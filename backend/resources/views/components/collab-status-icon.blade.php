{{-- Icono de estado. Props: type (issue|pull), status --}}
@props(['type' => 'issue', 'status'])
@php
    $color = match ($status) {
        'open', 'approved' => 'text-emerald-600 dark:text-emerald-400',
        'in_progress', 'in_review' => 'text-amber-600 dark:text-amber-400',
        'changes_requested' => 'text-rose-600 dark:text-rose-400',
        'resolved', 'merged' => 'text-purple-600 dark:text-purple-400',
        default => 'text-zinc-500 dark:text-zinc-400',
    };
@endphp
<svg {{ $attributes->merge(['class' => "shrink-0 {$color}"]) }} viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
    @if ($type === 'issue')
        @if (in_array($status, ['resolved', 'closed'], true))
            <circle cx="8" cy="8" r="6.25"/><path stroke-linecap="round" stroke-linejoin="round" d="m5.5 8.2 1.8 1.8 3.3-3.6"/>
        @else
            <circle cx="8" cy="8" r="6.25"/><circle cx="8" cy="8" r="1.6" fill="currentColor" stroke="none"/>
        @endif
    @else
        @if ($status === 'merged')
            <circle cx="4.5" cy="3.5" r="1.6"/><circle cx="4.5" cy="12.5" r="1.6"/><circle cx="11.5" cy="8.5" r="1.6"/><path d="M4.5 5.1v5.8M4.5 5.5c0 2.2 1.8 3 5.4 3"/>
        @elseif ($status === 'closed')
            <circle cx="4.5" cy="3.5" r="1.6"/><circle cx="4.5" cy="12.5" r="1.6"/><path d="M4.5 5.1v5.8M10 3l3 3m0-3-3 3"/><circle cx="11.5" cy="12.5" r="1.6"/>
        @else
            <circle cx="4.5" cy="3.5" r="1.6"/><circle cx="4.5" cy="12.5" r="1.6"/><circle cx="11.5" cy="12.5" r="1.6"/><path d="M4.5 5.1v5.8M11.5 10.9V6.5c0-1.4-.8-2.2-2.2-2.2H7.5m1.5-1.6L7.4 4.3 9 5.9"/>
        @endif
    @endif
</svg>
