{{-- Botón / enlace con estilo de botón. Variantes: primary, purple, secondary, ghost, success, danger. Tamaños: sm, md, lg. --}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'submit'])
@php
    $variantClasses = match ($variant) {
        'secondary' => 'border border-zinc-300 bg-white text-zinc-800 hover:border-zinc-400 hover:bg-zinc-50 hover:text-zinc-950 focus-visible:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700/80 dark:hover:text-white',
        'ghost' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 focus-visible:ring-zinc-400 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
        'purple', 'fuchsia' => 'bg-rose-600 text-white hover:bg-rose-700 focus-visible:ring-rose-500',
        'amethyst' => 'bg-purple-600 text-white hover:bg-purple-700 focus-visible:ring-purple-500',
        'teal' => 'bg-emerald-700 text-white hover:bg-emerald-800 focus-visible:ring-emerald-500',
        'success' => 'bg-emerald-700 text-white hover:bg-emerald-800 focus-visible:ring-emerald-500',
        'danger' => 'border border-rose-600 bg-white text-rose-700 hover:bg-rose-600 hover:text-white focus-visible:ring-rose-500 dark:bg-transparent dark:text-rose-300 dark:hover:text-white',
        default => 'bg-rose-600 text-white hover:bg-rose-700 focus-visible:ring-rose-500',
    };
    $sizeClasses = match ($size) {
        'sm' => 'gap-1.5 px-2.5 py-1 text-xs',
        'lg' => 'gap-2 px-4 py-2.5 text-sm',
        default => 'gap-1.5 px-3 py-1.5 text-sm',
    };
    $classes = 'inline-flex cursor-pointer items-center justify-center rounded-md font-bold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-white active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 dark:focus-visible:ring-offset-zinc-900 '.$variantClasses.' '.$sizeClasses;
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
