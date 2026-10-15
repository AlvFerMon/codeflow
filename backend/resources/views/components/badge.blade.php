{{-- Etiqueta redondeada. Acepta `color` (heredado) o `variant` (diseño nuevo): zinc/slate, green/success, red/danger, amber/warning, blue/info, purple/indigo. --}}
@props(['color' => null, 'variant' => null, 'size' => 'sm'])
@php
    $tone = $variant ?? $color ?? 'zinc';
    $variantClasses = match ($tone) {
        'green', 'success', 'emerald', 'teal' => 'border-[#2EC4B6]/30 bg-[#2EC4B6]/15 text-[#0E8377] dark:border-[#2EC4B6]/40 dark:bg-[#2EC4B6]/20 dark:text-[#4DE3D6]',
        'red', 'danger', 'rose', 'fuchsia' => 'border-[#FF3366]/30 bg-[#FF3366]/15 text-[#FF3366] dark:border-[#FF3366]/40 dark:bg-[#FF3366]/20 dark:text-[#FF4D79]',
        'amber', 'warning', 'chartreuse' => 'border-[#CAFE48]/40 bg-[#CAFE48]/20 text-[#486000] dark:border-[#CAFE48]/40 dark:bg-[#CAFE48]/25 dark:text-[#CAFE48]',
        'blue', 'info', 'sky' => 'border-sky-500/20 bg-sky-500/10 text-sky-700 dark:border-sky-500/30 dark:bg-sky-500/15 dark:text-sky-400',
        'purple', 'indigo', 'amethyst' => 'border-[#9448BC]/30 bg-[#9448BC]/15 text-[#9448BC] dark:border-[#9448BC]/40 dark:bg-[#9448BC]/20 dark:text-[#C687EB]',
        default => 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-700/80 dark:bg-zinc-800 dark:text-zinc-300',
    };
    $sizeClasses = match ($size) {
        'xs' => 'px-1.5 py-0.5 text-[11px]',
        'md' => 'px-3 py-1 text-xs',
        default => 'px-2 py-0.5 text-xs',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 whitespace-nowrap rounded-full border font-medium {$variantClasses} {$sizeClasses}"]) }}>{{ $slot }}</span>
