{{-- Tarjeta KPI del dashboard (diseño del compañero). Slots opcionales: icon, footer. Con href se convierte en enlace. --}}
@props([
    'href' => null,
    'title',
    'value',
    'change' => null,
    'accent' => 'blue',
])
@php
    $accentBorder = match ($accent) {
        'green', 'emerald', 'teal' => 'hover:border-emerald-500',
        'purple', 'amethyst' => 'hover:border-purple-500',
        'amber', 'chartreuse', 'yellow' => 'hover:border-[#9BC22E]',
        'fuchsia', 'hot-fuchsia', 'rose', 'pink' => 'hover:border-rose-500',
        default => 'hover:border-emerald-500',
    };
    $iconBg = match ($accent) {
        'green', 'emerald', 'teal' => 'bg-[#2EC4B6]/15 text-[#0E8377] dark:bg-[#2EC4B6]/20 dark:text-[#4DE3D6]',
        'purple', 'amethyst' => 'bg-[#9448BC]/15 text-[#9448BC] dark:bg-[#9448BC]/25 dark:text-[#C687EB]',
        'amber', 'chartreuse', 'yellow' => 'bg-[#CAFE48]/20 text-[#486000] dark:bg-[#CAFE48]/25 dark:text-[#CAFE48]',
        'fuchsia', 'hot-fuchsia', 'rose', 'pink' => 'bg-[#FF3366]/15 text-rose-600 dark:bg-[#FF3366]/25 dark:text-[#FF4D79]',
        default => 'bg-[#2EC4B6]/15 text-[#0E8377] dark:bg-[#2EC4B6]/20 dark:text-[#4DE3D6]',
    };
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => "group relative block overflow-hidden ficha rounded-lg p-5 transition-colors duration-200 {$accentBorder}"]) }}>
    <div class="flex items-center justify-between">
        <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $title }}</p>
        @isset($icon)
            <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $iconBg }}">{{ $icon }}</div>
        @endisset
    </div>
    <div class="mt-3 flex items-baseline gap-2">
        <span class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $value }}</span>
        @if ($change)
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $change }}</span>
        @endif
    </div>
    @isset($footer)
        <div class="mt-3 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">{{ $footer }}</div>
    @endisset
</{{ $tag }}>
