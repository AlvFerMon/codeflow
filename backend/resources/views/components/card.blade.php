{{-- Tarjeta base del diseño: borde zinc, esquinas xl, cabecera opcional con título, subtítulo y acciones. --}}
@props(['title' => null, 'subtitle' => null])
<section {{ $attributes->merge(['class' => 'rounded-xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900']) }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-3 border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
                @endif
            </div>
            {{ $actions ?? '' }}
        </header>
    @endif
    {{ $slot }}
</section>
