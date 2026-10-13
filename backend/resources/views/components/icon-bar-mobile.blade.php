@props(['items' => []])

<div class="gnav-mobile" aria-hidden="false">
    <nav class="gnav" aria-label="Navegación principal">
        {{-- Indicador elástico --}}
        <span class="gnav-ind" data-phase="idle" aria-hidden="true"></span>

        @foreach ($items as $item)
            <a
                href="{{ route($item['route']) }}"
                class="gnav-slot"
                @if ($item['active']) aria-current="page" data-active="true" @endif
            >
                {{ $item['label'] }}
                @if (($item['count'] ?? 0) > 0)
                    <span class="gnav-badge">{{ $item['count'] > 99 ? '99+' : $item['count'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
