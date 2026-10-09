{{-- Etiqueta redondeada. Acepta `color` (heredado) o `variant` (diseño nuevo): zinc/slate, green/success, red/danger, amber/warning, blue/info, purple/indigo. --}}
@props(['color' => null, 'variant' => null, 'size' => 'sm'])
@php
    $tone = $variant ?? $color ?? 'zinc';
    $variantClasses = match ($tone) {
        'green', 'success', 'emerald' => 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-400',
        'red', 'danger', 'rose' => 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/15 dark:text-rose-400',
        'amber', 'warning' => 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-400',
        'blue', 'info', 'sky' => 'border-sky-500/20 bg-sky-500/10 text-sky-700 dark:border-sky-500/30 dark:bg-sky-500/15 dark:text-sky-400',
        'purple', 'indigo' => 'border-purple-500/20 bg-purple-500/10 text-purple-700 dark:border-purple-500/30 dark:bg-purple-500/15 dark:text-purple-400',
        default => 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-700/80 dark:bg-zinc-800 dark:text-zinc-300',
    };
    $sizeClasses = match ($size) {
        'xs' => 'px-1.5 py-0.5 text-[11px]',
        'md' => 'px-3 py-1 text-xs',
        default => 'px-2 py-0.5 text-xs',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 whitespace-nowrap rounded-full border font-medium {$variantClasses} {$sizeClasses}"]) }}>{{ $slot }}</span>
