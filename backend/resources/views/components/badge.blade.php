{{-- Etiqueta con aspecto de sello: borde de tinta y sin relleno. `shape` dice el estado por la forma: done (redondo), closed (tachado), pending (rectangular). Acepta `color` (heredado) o `variant`: zinc/slate, green/success, red/danger, amber/warning, blue/info, purple/indigo. --}}
@props(['color' => null, 'variant' => null, 'size' => 'sm', 'shape' => null])
@php
    $tone = $variant ?? $color ?? 'zinc';
    $variantClasses = match ($tone) {
        'green', 'success', 'emerald', 'teal' => 'text-emerald-700 dark:text-emerald-300',
        'red', 'danger', 'rose', 'fuchsia' => 'text-rose-600 dark:text-rose-300',
        'amber', 'warning', 'chartreuse' => 'text-[#486000] bg-[#CAFE48]/25 dark:text-[#CAFE48] dark:bg-[#CAFE48]/10',
        'blue', 'info', 'sky' => 'text-sky-700 dark:text-sky-300',
        'purple', 'indigo', 'amethyst' => 'text-purple-600 dark:text-purple-300',
        default => 'text-zinc-600 dark:text-zinc-400',
    };
    $sizeClasses = match ($size) {
        'xs' => 'px-1 text-[10px] leading-4',
        'md' => 'px-2 py-0.5 text-xs',
        default => 'px-1.5 text-[11px] leading-[18px]',
    };
    $shapeClasses = match ($shape) {
        'done' => 'rounded-full -rotate-1',
        'closed' => 'rounded-[3px] line-through decoration-[1.5px]',
        'pending' => 'rounded-[3px] rotate-[0.75deg]',
        default => 'rounded-[3px]',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 whitespace-nowrap {$shapeClasses} border-[1.5px] border-current font-bold uppercase tracking-wide {$variantClasses} {$sizeClasses}"]) }}>{{ $slot }}</span>
