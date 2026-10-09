@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
])

@php
    $variantClasses = match ($variant) {
        'secondary' => 'bg-white text-zinc-800 border border-zinc-300/80 hover:bg-zinc-50 hover:text-zinc-950 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-700 dark:hover:bg-zinc-700/80 shadow-xs',
        'ghost' => 'text-zinc-600 hover:text-zinc-950 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800',
        'purple' => 'bg-purple-600 text-white hover:bg-purple-500 shadow-xs shadow-purple-600/20 focus-visible:ring-purple-500',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-500 shadow-xs shadow-rose-600/20 focus-visible:ring-rose-500',
        default => 'bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white shadow-xs focus-visible:ring-zinc-900',
    };

    $sizeClasses = match ($size) {
        'sm' => 'px-2.5 py-1 text-xs gap-1.5',
        'lg' => 'px-4 py-2.5 text-sm gap-2.5',
        default => 'px-3 py-1.5 text-xs font-medium gap-2',
    };

    $baseClasses = 'inline-flex items-center justify-center rounded-lg font-medium transition-all duration-150 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none active:scale-[0.98]';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "{$baseClasses} {$variantClasses} {$sizeClasses}"]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => "{$baseClasses} {$variantClasses} {$sizeClasses}"]) }}>
        {{ $slot }}
    </button>
@endif
