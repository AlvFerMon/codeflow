@props([
    'title',
    'value',
    'change' => null,
    'trend' => 'up',
    'accent' => 'blue',
])

@php
    $accentBorder = match ($accent) {
        'green', 'emerald' => 'hover:border-emerald-500/50 group-hover:text-emerald-500',
        'purple' => 'hover:border-purple-500/50 group-hover:text-purple-500',
        'amber' => 'hover:border-amber-500/50 group-hover:text-amber-500',
        default => 'hover:border-sky-500/50 group-hover:text-sky-500',
    };

    $iconBg = match ($accent) {
        'green', 'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400',
        'purple' => 'bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400',
        default => 'bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-400',
    };
@endphp

<div {{ $attributes->merge(['class' => "group relative overflow-hidden rounded-xl bg-white p-5 shadow-xs border border-zinc-200/80 transition-all duration-200 hover:shadow-md dark:bg-zinc-900 dark:border-zinc-800 {$accentBorder}"]) }}>
    <div class="flex items-center justify-between">
        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ $title }}</p>
        @if (isset($icon))
            <div class="h-9 w-9 rounded-lg flex items-center justify-center {{ $iconBg }}">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="mt-3 flex items-baseline gap-2">
        <span class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $value }}</span>
        @if ($change)
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                {{ $change }}
            </span>
        @endif
    </div>

    @if (isset($footer))
        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800 text-xs text-zinc-500 dark:text-zinc-400">
            {{ $footer }}
        </div>
    @endif
</div>
