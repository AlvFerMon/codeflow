{{-- Plantilla de la versión móvil (solo consulta): barra superior con el logo y barra inferior
     con indicador elástico Bencho. Rutas: mobile.activity, mobile.notifications, etc. --}}
@props(['title', 'tab'])
@php
    $user   = auth()->user();
    $unread = $user->unreadNotificationsCount();

    // Items de la barra inferior — mismas 4 secciones de la versión móvil dedicada.
    // 'active' se calcula comparando la prop $tab con la clave de cada item.
    $mobileItems = [
        ['label' => 'Actividad',  'route' => 'mobile.activity',      'active' => $tab === 'activity'],
        ['label' => 'Avisos',     'route' => 'mobile.notifications',  'active' => $tab === 'notifications', 'count' => $unread],
        ['label' => 'Proyectos',  'route' => 'mobile.projects',       'active' => $tab === 'projects'],
        ['label' => 'Cuenta',     'route' => 'mobile.account',        'active' => $tab === 'account'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    {{-- viewport-fit=cover para que el contenido llegue al borde en iPhone con notch --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · CodeFlow</title>
    @include('layouts._icons')
    @include('layouts._theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">

    {{-- Barra superior --}}
    <header class="sticky top-0 z-30 border-b border-zinc-200/80 bg-white/90 pt-[env(safe-area-inset-top)] backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90">
        <div class="mx-auto flex h-14 max-w-xl items-center gap-3 px-4">
            <x-logo :wordmark="false" size="h-7 w-7" />
            <h1 class="text-lg font-semibold tracking-tight">{{ $title }}</h1>
        </div>
    </header>

    {{-- Contenido — padding-bottom reserva espacio para la barra inferior + safe-area iOS --}}
    <main class="mx-auto max-w-xl px-4 pt-4 pb-[calc(5.5rem+env(safe-area-inset-bottom))]">
        <x-flash />
        {{ $slot }}
    </main>

    {{-- Barra de navegación inferior con indicador elástico (Bencho Icon bar) --}}
    {{-- Siempre visible; no necesita md:hidden porque este layout es exclusivo móvil. --}}
    <div class="gnav-mobile" role="navigation" aria-label="Navegación principal">
        <nav class="gnav" aria-label="Navegación principal">
            {{-- Indicador elástico — JS escribe --ind-move e --ind-size --}}
            <span class="gnav-ind" data-phase="idle" aria-hidden="true"></span>

            @foreach ($mobileItems as $item)
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

</body>
</html>
