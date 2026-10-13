{{-- Componente: Verificación de código numérico ("One-time code" / OTP) de Bencho
     Uso: <x-one-time-code name="code" :length="6" :value="old('code')" :error="$errors->has('code')" />

     Arquitectura Bencho:
       - Input nativo único invisible para autocompletado del sistema, portapapeles y lectores.
       - Filtro SVG líquido Goo en .liq-defs fuera del flujo.
       - Física de resorte MERGE (k = 0.075, zeta = 0.42) en el cursor líquido.
       - Animación Pop (POP_MS = 260ms) en cada entrada de dígito.
       - Secuencia de rechazo y drenaje de dígitos (SHAKE_MS = 620ms) en error.
--}}
@props([
    'name' => 'code',
    'length' => 6,
    'value' => old($name, ''),
    'autofocus' => true,
    'error' => false,
])

@php
    $length = (int) $length;
    $instanceId = 'otp-' . uniqid();
    $filterId = 'otp-goo-' . uniqid();
    $cleanValue = preg_replace('/\D/', '', (string) $value);
@endphp

<div class="otp-wrap">
    {{-- Filtro SVG Goo aislado fuera del flujo de layout (.liq-defs) --}}
    <svg class="liq-defs" aria-hidden="true" focusable="false">
        <defs>
            <filter id="{{ $filterId }}" x="-20%" y="-20%" width="140%" height="140%" color-interpolation-filters="sRGB">
                <feGaussianBlur in="SourceGraphic" stdDeviation="3" result="blur" />
                <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 24 -12" result="goo" />
            </filter>
        </defs>
    </svg>

    <div
        id="{{ $instanceId }}"
        class="otp"
        data-otp
        data-length="{{ $length }}"
        data-phase="{{ $error ? 'no' : 'idle' }}"
        data-focused="false"
        style="--len: {{ $length }};"
    >
        {{-- Input nativo transparente para compatibilidad total con el SO --}}
        <input
            type="text"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ $cleanValue }}"
            inputmode="numeric"
            pattern="[0-9]*"
            autocomplete="one-time-code"
            maxlength="{{ $length }}"
            class="otp-native"
            aria-label="Código de verificación de {{ $length }} dígitos"
            @if($autofocus) autofocus @endif
            required
        >

        {{-- Bandeja líquida con el filtro SVG Goo para fusión de cápsula y celdas --}}
        <div class="otp-goo" style="filter: url(#{{ $filterId }});" aria-hidden="true">
            <div class="otp-blob-track">
                @for ($i = 0; $i < $length; $i++)
                    <span class="otp-blob" data-slot="{{ $i }}" data-filled="{{ $i < strlen($cleanValue) ? 'true' : 'false' }}"></span>
                @endfor
                {{-- Cursor líquido / píldora conectora con resorte MERGE --}}
                <span class="otp-pill"></span>
            </div>
        </div>

        {{-- Capa visual de celdas y números --}}
        <div class="otp-slabs" aria-hidden="true">
            @for ($i = 0; $i < $length; $i++)
                @php
                    $char = substr($cleanValue, $i, 1) ?: '';
                    $isActive = ($i === min(strlen($cleanValue), $length - 1));
                @endphp
                <div class="otp-slot" data-slot="{{ $i }}" data-active="{{ $isActive ? 'true' : 'false' }}">
                    <span class="otp-slab"></span>
                    <span class="otp-char">{{ $char }}</span>
                </div>
            @endfor
        </div>
    </div>
</div>
