{{-- Botón / enlace con estilo de botón. Variantes: primary, purple, secondary, ghost, success, danger. Tamaños: sm, md, lg. --}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'submit'])
@php
    $variantClasses = match ($variant) {
        'secondary' => 'border border-zinc-300/80 bg-white text-zinc-800 shadow-xs hover:bg-zinc-50 hover:text-zinc-950 focus-visible:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700/80 dark:hover:text-white',
        'ghost' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 focus-visible:ring-zinc-400 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
        'purple', 'fuchsia' => 'bg-[#FF3366] text-white shadow-xs shadow-[#FF3366]/20 hover:bg-[#e62052] focus-visible:ring-[#FF3366]',
        'amethyst' => 'bg-[#9448BC] text-white shadow-xs shadow-[#9448BC]/20 hover:bg-[#8038a8] focus-visible:ring-[#9448BC]',
        'teal' => 'bg-[#2EC4B6] text-white shadow-xs shadow-[#2EC4B6]/20 hover:bg-[#25ab9e] focus-visible:ring-[#2EC4B6]',
        'success' => 'bg-emerald-600 text-white shadow-xs shadow-emerald-600/20 hover:bg-emerald-500 focus-visible:ring-emerald-500',
        'danger' => 'bg-rose-600 text-white shadow-xs shadow-rose-600/20 hover:bg-rose-500 focus-visible:ring-rose-500',
        default => 'bg-[#FF3366] text-white shadow-xs hover:bg-[#e62052] focus-visible:ring-[#FF3366]',
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
