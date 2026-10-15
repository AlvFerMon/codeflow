{{-- Componente: buscador elástico Bencho "Search / Seek"
     Uso: <x-search-seek :value="request('q')" placeholder="Buscar repositorios…" />

     El formulario actúa como un único objeto elástico:
       - Cerrado: círculo compacto de 36px con la lente SVG.
       - Abierto: se expande con física de resorte (springOf) a cápsula pill.
       - La lente no se traslada; la caja crece alrededor.
       - El texto se revela en el último tercio de la expansión (--say).
       - Efecto magnético hacia el cursor al estar cerrado (give = 50).
--}}
@props([
    'action' => route('explore'),
    'method' => 'GET',
    'name' => 'q',
    'value' => '',
    'placeholder' => 'Buscar repositorios…',
])

<form
    action="{{ $action }}"
    method="{{ $method }}"
    role="search"
    {{ $attributes->merge(['class' => 'sek']) }}
    data-sek
>
    {{-- Botón de la lente SVG: permanece geométricamente centrado en el círculo en reposo --}}
    <button type="button" class="sek-lens" aria-label="Abrir buscador" tabindex="-1">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
    </button>

    {{-- Campo de entrada revelado en el último tercio de expansión (--say) --}}
    <div class="sek-field">
        <label for="sek-input-{{ $name }}" class="sr-only">{{ $placeholder }}</label>
        <input
            id="sek-input-{{ $name }}"
            type="search"
            name="{{ $name }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            aria-label="{{ $placeholder }}"
            class="sek-input"
            data-search-shortcut
            maxlength="100"
            autocomplete="off"
            spellcheck="false"
        >

        {{-- Chip de atajo de teclado Ctrl K --}}
        <kbd class="sek-k" aria-hidden="true" title="Presiona Ctrl+K para buscar">Ctrl K</kbd>

        {{-- Botón de borrado rápido (visible cuando hay texto) --}}
        <button type="button" class="sek-clear" aria-label="Limpiar búsqueda" title="Borrar">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</form>
