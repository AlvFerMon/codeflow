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
        'green', 'emerald' => 'hover:border-emerald-500/50',
        'purple' => 'hover:border-purple-500/50',
        'amber' => 'hover:border-amber-500/50',
        default => 'hover:border-sky-500/50',
    };
    $iconBg = match ($accent) {
        'green', 'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400',
        'purple' => 'bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400',
        default => 'bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-400',
    };
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => "group relative block overflow-hidden rounded-xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 {$accentBorder}"]) }}>
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
