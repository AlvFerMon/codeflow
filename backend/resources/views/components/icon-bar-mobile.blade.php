{{-- Componente: barra de navegación inferior para móvil (Bencho Icon bar)
     Uso:  <x-icon-bar-mobile :items="$mobileNavItems" />

     El div.gnav-mobile está oculto en escritorio ≥ 1024px (lg:hidden) y visible en < 1024px.
     En el CSS, .gnav-mobile es position:fixed + pointer-events:none. El
     .gnav interior es el pill clicable con pointer-events:auto.
     El padding-bottom usa env(safe-area-inset-bottom) para iOS.

     Cada item de $items debe tener:
       label   string  — texto del enlace
       route   string  — nombre de ruta Laravel
       active  bool    — si el slot está activo
       count   int?    — (opcional) badge de elementos sin leer
--}}
@props(['items' => []])

<div class="gnav-mobile lg:hidden" role="navigation" aria-label="Navegación principal">
    <nav class="gnav" aria-label="Navegación principal">
        {{-- Indicador elástico — JS escribe --ind-move e --ind-size sobre el .gnav --}}
        <span class="gnav-ind" data-phase="idle" aria-hidden="true"></span>

        @foreach ($items as $item)
            <a
                href="{{ route($item['route']) }}"
                class="gnav-slot"
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
</div>
