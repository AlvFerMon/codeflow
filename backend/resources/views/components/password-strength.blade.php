{{-- Componente: Validación y Medidor de Fortaleza de Contraseña (UI Arc)
     Uso: <x-password-strength label="Contraseña" name="password" required />

     Características:
       - Input con botón interactivo de revelación (ojo) y aria-pressed.
       - Medidor de 4 segmentos (role="meter", aria-valuenow="0..4").
       - Región de estado accesible (role="status", aria-live="polite").
       - Checklist de 4 reglas con contador de caracteres restantes en vivo.
       - Integración nativa con tokens de diseño Zinc/Purple y modo oscuro.
--}}
@props([
    'label' => 'Contraseña',
    'name' => 'password',
    'required' => false,
    'autocomplete' => 'new-password',
    'minlength' => 8,
    'placeholder' => null,
    'help' => null,
])

@php
    $id = 'pws-' . uniqid();
    $statusId = $id . '-status';
    $rulesId = $id . '-rules';
@endphp

<div
    class="pws"
    data-pws
    data-score="0"
    data-minlength="{{ $minlength }}"
>
    @if ($label)
        <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
        </label>
    @endif

    <div class="pws-field" data-has-error="{{ $errors->has($name) ? 'true' : 'false' }}">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            autocomplete="{{ $autocomplete }}"
            minlength="{{ $minlength }}"
            @if ($required) required @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            aria-describedby="{{ $statusId }} {{ $rulesId }}"
            {{ $attributes->merge(['class' => 'pws-input']) }}
        >

        {{-- Botón para mostrar / ocultar contraseña --}}
        <button
            type="button"
            class="pws-toggle"
            aria-label="Mostrar contraseña"
            aria-pressed="false"
            tabindex="-1"
            title="Mostrar u ocultar contraseña"
        >
            {{-- Icono Ojo (abierto) --}}
            <svg class="pws-eye-open" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            {{-- Icono Ojo tachado (oculto por defecto) --}}
            <svg class="pws-eye-closed hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        </button>
    </div>

    {{-- Medidor de 4 segmentos y región de estado accesible --}}
    <div class="pws-indicator">
        <div class="pws-header">
            <span class="pws-header-title">Seguridad de la clave</span>
            <span
                id="{{ $statusId }}"
                class="pws-status"
                role="status"
                aria-live="polite"
            >
                Demasiado corta
            </span>
        </div>

        <div
            class="pws-meter"
            role="meter"
            aria-valuenow="0"
            aria-valuemin="0"
            aria-valuemax="4"
            aria-valuetext="Fortaleza: Demasiado corta"
        >
            <span class="pws-seg" data-index="0"></span>
            <span class="pws-seg" data-index="1"></span>
            <span class="pws-seg" data-index="2"></span>
            <span class="pws-seg" data-index="3"></span>
        </div>
    </div>

    {{-- Lista de reglas en vivo (Checklist) --}}
    <ul id="{{ $rulesId }}" class="pws-rules" aria-label="Requisitos de la contraseña">
        <li class="pws-rule" data-rule="length" data-passed="false">
            <span class="pws-rule-icon" aria-hidden="true">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </span>
            <span>Mínimo {{ $minlength }} caracteres <span class="pws-counter" aria-hidden="true"></span></span>
        </li>
        <li class="pws-rule" data-rule="cases" data-passed="false">
            <span class="pws-rule-icon" aria-hidden="true">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </span>
            <span>Mayúsculas y minúsculas</span>
        </li>
        <li class="pws-rule" data-rule="number" data-passed="false">
            <span class="pws-rule-icon" aria-hidden="true">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </span>
            <span>Al menos un número (0-9)</span>
        </li>
        <li class="pws-rule" data-rule="symbol" data-passed="false">
            <span class="pws-rule-icon" aria-hidden="true">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </span>
            <span>Al menos un símbolo o especial</span>
        </li>
    </ul>

    @if ($help)
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>
    @enderror
</div>
