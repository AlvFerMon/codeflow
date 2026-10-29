{{-- Tarjeta base del diseño: borde zinc, esquinas xl, cabecera opcional con título, subtítulo y acciones. --}}
@props(['title' => null, 'subtitle' => null])
<section {{ $attributes->merge(['class' => 'ficha rounded-lg']) }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <div class="min-w-0">
                <h2 class="text-sm font-bold text-zinc-900 dark:text-white">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
                @endif
            </div>
            {{ $actions ?? '' }}
        </header>
    @endif
    {{ $slot }}
</section>
