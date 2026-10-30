<!DOCTYPE html>
<html lang="es" class="h-full" {!! \App\Support\Preferences::htmlAttributeString(auth()->user()) !!}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}CodeFlow</title>
    @include('layouts._icons')
    @include('layouts._theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $user = auth()->user();
    // Sin email verificado se navega como visitante (solo lo público) hasta confirmar el correo
    $verified = $user?->hasVerifiedEmail() ?? false;
    // En un teléfono no existe "Explorar repositorios públicos"
    $isPhone = \App\Http\Controllers\Web\Mobile\MobileController::isPhone(request());
    $unread = $verified ? $user->unreadNotificationsCount() : 0;

    // ── Items de navegación — escritorio ──────────────────────────────────────
    // "Explorar" se excluye en teléfonos porque la versión móvil no lo tiene.
    $navItems = array_filter([
        ['label' => 'Inicio',         'route' => 'home',               'active' => request()->routeIs('home')],
        $verified ? ['label' => 'Proyectos',    'route' => 'projects.index',     'active' => request()->routeIs('projects.*', 'repositories.*', 'files.*', 'branches.*', 'commits.*', 'issues.*', 'pulls.*', 'members.*')] : null,
        $isPhone  ? null : ['label' => 'Explorar',     'route' => 'explore',           'active' => request()->routeIs('explore')],
        $verified ? ['label' => 'Actividad',    'route' => 'activity.index',     'active' => request()->routeIs('activity.index')] : null,
        $verified ? ['label' => 'Notificaciones', 'route' => 'notifications.index', 'active' => request()->routeIs('notifications.*'), 'count' => $unread] : null,
        ['label' => 'Descargas', 'route' => 'downloads', 'active' => request()->routeIs('downloads')],
    ]);

    // ── Items de navegación — barra inferior móvil ────────────────────────────
    // Sin "Explorar" (no existe en móvil). "Avisos" es más corto que
    // "Notificaciones" y cabe mejor en el pill estrecho de la barra inferior.
    $mobileNavItems = array_filter([
        ['label' => 'Inicio',     'route' => 'home',               'active' => request()->routeIs('home')],
        $verified ? ['label' => 'Proyectos', 'route' => 'projects.index',     'active' => request()->routeIs('projects.*', 'repositories.*', 'files.*', 'branches.*', 'commits.*', 'issues.*', 'pulls.*', 'members.*')] : null,
        $verified ? ['label' => 'Actividad', 'route' => 'activity.index',     'active' => request()->routeIs('activity.index')] : null,
        $verified ? ['label' => 'Avisos',    'route' => 'notifications.index', 'active' => request()->routeIs('notifications.*'), 'count' => $unread] : null,
    ]);

    $navLink   = 'rounded-lg px-3 py-1.5 text-xs transition-colors';
    $navActive = 'bg-zinc-100 font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-white';
    $navIdle   = 'font-medium text-zinc-600 hover:bg-zinc-100/80 hover:text-zinc-950 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-100';
    $menuItem  = 'flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800';
@endphp
<body class="flex min-h-full flex-col bg-[#F2F4FF] font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100{{ $verified ? ' has-mobile-nav' : '' }}">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:shadow-lg dark:focus:bg-zinc-900">Saltar al contenido</a>

    <header class="sticky top-0 z-40 w-full border-b border-zinc-300 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-3">
                <div class="flex items-center gap-6">
                    {{-- Menú hamburguesa — sólo visible en < md --}}
                    <details class="relative lg:hidden" data-dropdown>
                        <summary class="flex cursor-pointer list-none rounded-lg p-2 text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Abrir menú">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </summary>
                        <nav class="absolute left-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                            @foreach ($navItems as $item)
                                <a href="{{ route($item['route']) }}" @class([$menuItem, 'font-semibold text-zinc-950! dark:text-white!' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
                                    {{ $item['label'] }}
                                    @if (($item['count'] ?? 0) > 0)<span class="ml-auto rounded-full bg-[#9448BC]/15 px-1.5 text-[10px] font-bold text-[#9448BC] dark:text-[#C687EB]">{{ $item['count'] > 99 ? '99+' : $item['count'] }}</span>@endif
                                </a>
                            @endforeach
                            @unless ($isPhone)
                            <form action="{{ route('explore') }}" method="GET" class="border-t border-zinc-100 p-2 dark:border-zinc-800">
                                <input type="search" name="q" placeholder="Buscar repositorios públicos…" aria-label="Buscar repositorios públicos"
                                    class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs focus:border-[#FF3366] focus:outline-none focus:ring-2 focus:ring-[#FF3366]/20 dark:border-zinc-700 dark:bg-zinc-800">
                            </form>
                            @endunless
                        </nav>
                    </details>

                    <a href="{{ route('home') }}" class="group flex items-center gap-2.5" aria-label="CodeFlow, inicio">
                        <x-logo size="h-8 w-8" class="transition-transform group-hover:scale-105" />
                        <span class="flex items-center gap-2">
                            <span class="hidden items-center rounded-lg bg-[#CAFE48]/15 px-1.5 py-0.5 text-[10px] font-semibold text-[#486000] ring-1 ring-inset ring-[#CAFE48]/30 sm:inline-flex dark:bg-[#CAFE48]/20 dark:text-[#CAFE48] dark:ring-[#CAFE48]/30">VCS v1.0</span>
                        </span>
                    </a>

                    {{-- Barra de navegación con indicador elástico (Bencho Icon bar) — desktop --}}
                    <x-icon-bar :items="$navItems" class="hidden lg:inline-flex" />
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    {{-- Buscador elástico Bencho "Search / Seek" (Ctrl/⌘ + K) --}}
                    @unless ($isPhone)
                    <div class="hidden sm:block" data-tour="buscar">
                        <x-search-seek :value="request()->routeIs('explore') ? request('q') : ''" placeholder="Buscar repositorios…" />
                    </div>
                    @endunless

                    @auth
                        @if ($verified)
                        @can('create', \App\Models\Project::class)
                            <span class="hidden sm:inline"><x-button variant="purple" size="sm" :href="route('projects.create')" title="Crear un proyecto nuevo" data-tour="nuevo-proyecto">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                <span>Nuevo</span>
                            </x-button></span>
                        @endcan

                        <a href="{{ route('notifications.index') }}" data-tour="avisos" class="relative rounded-lg p-2 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                            title="{{ $unread > 0 ? $unread.' notificaciones sin leer' : 'Notificaciones' }}">
                            <span class="sr-only">Notificaciones{{ $unread > 0 ? ' ('.$unread.' sin leer)' : '' }}</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            @if ($unread > 0)
                                <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-[#FF3366] ring-2 ring-white dark:ring-zinc-900"></span>
                            @endif
                        </a>
                        @endif

                        <details class="relative border-l border-zinc-200 pl-1 dark:border-zinc-800" data-dropdown>
                            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg p-1 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Menú de usuario" data-tour="menu-usuario">
                                <x-user-avatar :user="$user" />
                                <span class="hidden text-xs font-semibold text-zinc-800 lg:block dark:text-zinc-200">{{ $user->name }}</span>
                                @if ($user->isAdmin())
                                    <span class="hidden lg:inline"><x-badge variant="amber" size="xs">admin</x-badge></span>
                                @endif
                                <svg class="hidden h-3.5 w-3.5 text-zinc-400 sm:block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </summary>
                            <div class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="border-b border-zinc-100 px-3 py-2 dark:border-zinc-800">
                                    <p class="truncate text-xs font-semibold text-zinc-900 dark:text-white">{{ $user->name }}</p>
                                    <p class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="{{ $menuItem }}">Mi perfil y configuración</a>
                                <a href="{{ route('projects.index') }}" class="{{ $menuItem }}">Mis proyectos</a>
                                @if ($verified)
                                    <a href="{{ route('trash.index') }}" class="{{ $menuItem }}">Papelera</a>
                                @endif
                                @if ($verified)
                                    <form method="POST" action="{{ route('onboarding.restart') }}">
                                        @csrf
                                        <button class="{{ $menuItem }}">Ver el tutorial otra vez</button>
                                    </form>
                                @endif
                                @if ($verified)
                                    <a href="{{ route('mobile.enable') }}" class="{{ $menuItem }} md:hidden">Versión móvil (sencilla)</a>
                                @endif
                                <div class="border-t border-zinc-100 px-3 py-2 dark:border-zinc-800">
                                    <p class="mb-1.5 text-[11px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tema</p>
                                    @include('layouts._theme-switch')
                                </div>
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-zinc-100 dark:border-zinc-800">
                                    @csrf
                                    <button class="{{ $menuItem }} text-rose-600! dark:text-rose-400!">Cerrar sesión</button>
                                </form>
                            </div>
                        </details>
                    @else
                        <a href="{{ route('login') }}" class="{{ $navLink }} {{ $navIdle }}">Entrar</a>
                        <x-button variant="purple" size="sm" :href="route('register')">Registrarse</x-button>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main id="contenido" class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        <x-flash />
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-zinc-300 bg-white py-8 text-xs text-zinc-500 dark:border-zinc-800/80 dark:bg-zinc-900/60 dark:text-zinc-400">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 sm:flex-row sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 font-medium text-emerald-600 dark:text-emerald-400">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
                    Sistemas VCS operativos
                </span>
                <span class="text-zinc-300 dark:text-zinc-700">•</span>
                <span>CodeFlow · TFG 2.º DAM</span>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
                @unless ($isPhone)
                    <a href="{{ route('explore') }}" class="transition-colors hover:text-zinc-900 dark:hover:text-zinc-100">Explorar</a>
                @endunless
                <a href="{{ route('downloads') }}" class="transition-colors hover:text-zinc-900 dark:hover:text-zinc-100">Descargas</a>
                <a href="https://github.com/AlvFerMon/codeflow" target="_blank" rel="noopener" class="transition-colors hover:text-zinc-900 dark:hover:text-zinc-100">Repositorio en GitHub</a>
                <a href="{{ route('legal', 'privacidad') }}" class="transition-colors hover:text-zinc-900 dark:hover:text-zinc-100">Privacidad</a>
                <a href="{{ route('legal', 'cookies') }}" class="transition-colors hover:text-zinc-900 dark:hover:text-zinc-100">Cookies</a>
                <a href="{{ route('legal', 'terminos') }}" class="transition-colors hover:text-zinc-900 dark:hover:text-zinc-100">Condiciones</a>
                <a href="{{ route('legal', 'aviso-legal') }}" class="transition-colors hover:text-zinc-900 dark:hover:text-zinc-100">Aviso legal</a>
                @guest
                    @include('layouts._theme-switch')
                @endguest
                <span class="text-zinc-500 dark:text-zinc-400">Hecho con Laravel y Tailwind</span>
            </div>
        </div>
    </footer>

    @include('layouts._confirm-dialog')

    {{-- Tutorial de bienvenida (visita guiada). Estado desde OnboardingService; lo pinta resources/js/onboarding.js --}}
    @if ($verified)
        @include('layouts._onboarding')
    @endif

    {{-- Barra de navegación inferior con indicador elástico (Bencho Icon bar) — móvil --}}
    {{-- Se renderiza para usuarios verificados; se oculta en escritorio (≥ 1024px / lg) con CSS. --}}
    {{-- La clase has-mobile-nav en <body> activa el padding-bottom solo en < 1024px. --}}
    @if ($verified)
        <x-icon-bar-mobile :items="$mobileNavItems" />
    @endif

</body>
</html>
