{{--
    Pestañas de navegación (enlaces) con un único estilo en toda la web.

    Props:
      items   [clave => ['label' => …, 'url' => …, 'count' => ?int, 'highlight' => ?bool, 'end' => ?bool]]
              count: recuento opcional · highlight: recuento resaltado (p. ej. no leídas) · end: alinear a la derecha
      active  clave de la pestaña actual
      variant 'underline' (secciones de una página) | 'pills' (filtros como Abiertas/Cerradas)
      label   nombre accesible de la navegación
--}}
@props(['items', 'active' => null, 'variant' => 'underline', 'label' => 'Pestañas'])
@php
    $pills = $variant === 'pills';
@endphp
<nav aria-label="{{ $label }}" {{ $attributes->class([
    'flex overflow-x-auto text-sm',
    'gap-1 border-b border-zinc-200 dark:border-zinc-800' => ! $pills,
    'w-fit max-w-full gap-0.5 rounded-lg border border-zinc-200 bg-white p-0.5 dark:border-zinc-800 dark:bg-zinc-900' => $pills,
]) }}>
    @foreach ($items as $key => $item)
        @php
            $isActive = (string) $key === (string) $active;
            $count = $item['count'] ?? null;
        @endphp
        <a href="{{ $item['url'] }}" @if ($isActive) aria-current="page" @endif @class([
            'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap transition-colors',
            'ml-auto' => $item['end'] ?? false,
            // Subrayado
            '-mb-px border-b-2 px-3 py-2' => ! $pills,
            'border-[#FF3366] font-medium text-zinc-900 dark:text-white' => ! $pills && $isActive,
            'border-transparent text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => ! $pills && ! $isActive,
            // Píldoras
            'rounded-md px-3 py-1' => $pills,
            'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-white' => $pills && $isActive,
            'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-100' => $pills && ! $isActive,
        ])>
            {{ $item['label'] }}
            @if ($count !== null && ($count > 0 || $pills))
                <span @class([
                    'rounded-full px-1.5 text-[11px] font-semibold tabular-nums',
                    'bg-[#9448BC]/15 text-[#9448BC] dark:bg-[#9448BC]/25 dark:text-[#C687EB]' => $item['highlight'] ?? false,
                    'bg-zinc-200/70 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300' => ! ($item['highlight'] ?? false),
                ])>{{ $count }}</span>
            @endif
        </a>
    @endforeach
</nav>
