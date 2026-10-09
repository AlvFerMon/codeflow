{{-- Plantilla de la versión móvil (solo consulta): barra superior con el logo y barra inferior con 4 pestañas. --}}
@props(['title', 'tab'])
@php
    $user = auth()->user();
    $unread = $user->unreadNotificationsCount();
    $tabs = [
        'activity' => ['Actividad', route('mobile.activity'), 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5'],
        'notifications' => ['Avisos', route('mobile.notifications'), 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
        'projects' => ['Proyectos', route('mobile.projects'), 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z'],
        'account' => ['Cuenta', route('mobile.account'), 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · CodeFlow</title>
    @include('layouts._icons')
    @include('layouts._theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <header class="sticky top-0 z-30 border-b border-zinc-200/80 bg-white/90 pt-[env(safe-area-inset-top)] backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90">
        <div class="mx-auto flex h-14 max-w-xl items-center gap-3 px-4">
            <x-logo :wordmark="false" size="h-7 w-7" />
            <h1 class="text-lg font-semibold tracking-tight">{{ $title }}</h1>
        </div>
    </header>

    <main class="mx-auto max-w-xl px-4 pt-4 pb-[calc(5.5rem+env(safe-area-inset-bottom))]">
        <x-flash />
        {{ $slot }}
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200/80 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95" aria-label="Secciones">
        <div class="mx-auto grid h-16 max-w-xl grid-cols-4">
            @foreach ($tabs as $key => [$label, $url, $icon])
                <a href="{{ $url }}" @if ($tab === $key) aria-current="page" @endif @class([
                    'relative flex flex-col items-center justify-center gap-0.5 text-[11px] font-medium',
                    'text-purple-600 dark:text-purple-400' => $tab === $key,
                    'text-zinc-500 dark:text-zinc-400' => $tab !== $key,
                ])>
                    <span class="relative">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        @if ($key === 'notifications' && $unread > 0)
                            <span class="absolute -top-1 -right-2 min-w-4 rounded-full bg-purple-600 px-1 text-center text-[10px] leading-4 font-semibold text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
                        @endif
                    </span>
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </nav>
</body>
</html>
