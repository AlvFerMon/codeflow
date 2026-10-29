{{-- Componente: barra de navegación con indicador elástico (Bencho Icon bar)
     Uso:  <x-icon-bar :items="$navItems" class="hidden md:inline-flex" />

     Cada item del array $items debe tener:
       label   string  — texto del enlace
       route   string  — nombre de ruta Laravel (usado con route())
       active  bool    — si este slot está activo en la solicitud actual
       count   int?    — (opcional) badge de elementos sin leer
       tour    string? — (opcional) ancla data-tour para el tutorial de bienvenida
--}}
@props(['items' => [], 'aria-label' => 'Principal'])

{{-- Las clases extra (p. ej. "hidden md:inline-flex") se fusionan con "gnav" en un único atributo class --}}
<nav
    aria-label="{{ $attributes->get('aria-label', 'Principal') }}"
    {{ $attributes->except('aria-label')->merge(['class' => 'gnav']) }}
>
    {{-- Indicador elástico — el JS escribe --ind-move e --ind-size sobre el .gnav --}}
    <span class="gnav-ind" data-phase="idle" aria-hidden="true"></span>

    @foreach ($items as $item)
        <a
            href="{{ route($item['route']) }}"
            class="gnav-slot"
            data-tour="nav-{{ \Illuminate\Support\Str::before($item['route'], '.') }}"
            @if ($item['active'])
                aria-current="page"
                data-active="true"
            @endif
        >
            {{ $item['label'] }}

            @if (($item['count'] ?? 0) > 0)
                <span class="gnav-badge" aria-label="{{ $item['count'] }} sin leer">
                    {{ $item['count'] > 99 ? '99+' : $item['count'] }}
                </span>
            @endif
        </a>
    @endforeach
</nav>
