@props([
    'variant' => 'zinc',
    'size' => 'sm',
])

@php
    $variantClasses = match ($variant) {
        'success', 'green' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30',
        'purple' => 'bg-purple-500/10 text-purple-700 border-purple-500/20 dark:bg-purple-500/15 dark:text-purple-400 dark:border-purple-500/30',
        'warning', 'amber' => 'bg-amber-500/10 text-amber-700 border-amber-500/20 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30',
        'danger', 'red' => 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/30',
        'blue', 'info' => 'bg-sky-500/10 text-sky-700 border-sky-500/20 dark:bg-sky-500/15 dark:text-sky-400 dark:border-sky-500/30',
        default => 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700/80',
    };

    $sizeClasses = match ($size) {
        'xs' => 'px-1.5 py-0.5 text-[11px]',
        'md' => 'px-3 py-1 text-xs',
        default => 'px-2 py-0.5 text-xs',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-medium rounded-full border transition-colors {$variantClasses} {$sizeClasses}"]) }}>
    {{ $slot }}
</span>
