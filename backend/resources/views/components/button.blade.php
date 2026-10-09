{{-- Botón / enlace con estilo de botón. Variantes: primary, purple, secondary, ghost, success, danger. Tamaños: sm, md, lg. --}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'submit'])
@php
    $variantClasses = match ($variant) {
        'secondary' => 'border border-zinc-300/80 bg-white text-zinc-800 shadow-xs hover:bg-zinc-50 hover:text-zinc-950 focus-visible:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700/80 dark:hover:text-white',
        'ghost' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 focus-visible:ring-zinc-400 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
        'purple' => 'bg-purple-600 text-white shadow-xs shadow-purple-600/20 hover:bg-purple-500 focus-visible:ring-purple-500',
        'success' => 'bg-emerald-600 text-white shadow-xs shadow-emerald-600/20 hover:bg-emerald-500 focus-visible:ring-emerald-500',
        'danger' => 'bg-rose-600 text-white shadow-xs shadow-rose-600/20 hover:bg-rose-500 focus-visible:ring-rose-500',
        default => 'bg-zinc-900 text-white shadow-xs hover:bg-zinc-800 focus-visible:ring-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white dark:focus-visible:ring-zinc-100',
    };
    $sizeClasses = match ($size) {
        'sm' => 'gap-1.5 px-2.5 py-1 text-xs',
        'lg' => 'gap-2 px-4 py-2.5 text-sm',
        default => 'gap-1.5 px-3 py-1.5 text-sm',
    };
    $classes = 'inline-flex cursor-pointer items-center justify-center rounded-lg font-medium transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-white active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 dark:focus-visible:ring-offset-zinc-900 '.$variantClasses.' '.$sizeClasses;
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
