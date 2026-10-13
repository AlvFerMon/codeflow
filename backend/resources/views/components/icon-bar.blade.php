@props(['items' => [], 'aria-label' => 'Principal'])

<nav class="gnav" aria-label="{{ $attributes->get('aria-label', 'Principal') }}" {{ $attributes->except('aria-label') }}>
    {{-- Indicador elástico — el JS escribe --ind-move e --ind-size sobre el .gnav --}}
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
