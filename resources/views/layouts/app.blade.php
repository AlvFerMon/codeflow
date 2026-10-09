<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale() ?? 'es') }}" class="h-full bg-zinc-50 dark:bg-zinc-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'CodeFlow - Plataforma de Flujo de Código y VCS' }}</title>

    <!-- Google Fonts / Instrument Sans -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-zinc-900 bg-zinc-50 dark:bg-zinc-950 dark:text-zinc-100 flex flex-col selection:bg-purple-500 selection:text-white">

    <!-- Navbar Principal -->
    <header class="sticky top-0 z-40 w-full border-b border-zinc-200/80 bg-white/85 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/85">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">
                
                <!-- Logo & Brand -->
                <div class="flex items-center gap-6">
                    <a href="{{ url('/') }}" class="flex items-center gap-2.5 group">
                        <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-purple-600 via-indigo-600 to-sky-500 p-0.5 shadow-sm group-hover:scale-105 transition-transform">
                            <div class="h-full w-full bg-zinc-950 rounded-[10px] flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-base font-bold tracking-tight text-zinc-950 dark:text-white">CodeFlow</span>
                            <span class="hidden sm:inline-flex items-center rounded-md bg-purple-50 px-1.5 py-0.5 text-[10px] font-semibold text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-950/50 dark:text-purple-300 dark:ring-purple-400/20">VCS v1.0</span>
                        </div>
                    </a>

                    <!-- Navigation Links (Desktop) -->
                    <nav class="hidden md:flex items-center gap-1">
                        <a href="{{ url('/') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white transition-colors">
                            Dashboard
                        </a>
                        <a href="#repositorios" class="px-3 py-1.5 text-xs font-medium rounded-lg text-zinc-600 hover:text-zinc-950 hover:bg-zinc-100/80 dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/60 transition-colors">
                            Repositorios
                        </a>
                        <a href="#pull-requests" class="px-3 py-1.5 text-xs font-medium rounded-lg text-zinc-600 hover:text-zinc-950 hover:bg-zinc-100/80 dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/60 transition-colors flex items-center gap-1.5">
                            Pull Requests
                            <span class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full bg-emerald-500/10 text-emerald-600 text-[10px] font-bold">5</span>
                        </a>
                        <a href="#issues" class="px-3 py-1.5 text-xs font-medium rounded-lg text-zinc-600 hover:text-zinc-950 hover:bg-zinc-100/80 dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/60 transition-colors flex items-center gap-1.5">
                            Issues
                            <span class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full bg-amber-500/10 text-amber-600 text-[10px] font-bold">8</span>
                        </a>
                        <a href="#actividad" class="px-3 py-1.5 text-xs font-medium rounded-lg text-zinc-600 hover:text-zinc-950 hover:bg-zinc-100/80 dark:text-zinc-400 dark:hover:text-zinc-100 dark:hover:bg-zinc-800/60 transition-colors">
                            Actividad
                        </a>
                    </nav>
                </div>

                <!-- Buscador simulado con Ctrl+K & Acciones rápidas -->
                <div class="flex items-center gap-3">
                    <div class="relative hidden sm:block w-52 md:w-64">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <input type="text" placeholder="Buscar repo, commit, PR..." 
                            class="w-full rounded-lg border border-zinc-200 bg-zinc-50/70 py-1.5 pl-9 pr-12 text-xs text-zinc-900 placeholder-zinc-400 focus:border-purple-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500/20 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-100 dark:placeholder-zinc-500 dark:focus:border-purple-400" />
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                            <kbd class="rounded border border-zinc-200 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-zinc-400 shadow-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">⌘K</kbd>
                        </div>
                    </div>

                    <!-- Botón Crear Nuevo -->
                    <x-button variant="purple" size="sm" class="hidden sm:inline-flex">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Nuevo</span>
                    </x-button>

                    <!-- Notificaciones -->
                    <button type="button" class="relative rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors">
                        <span class="sr-only">Notificaciones</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-purple-600 ring-2 ring-white dark:ring-zinc-900"></span>
                    </button>

                    <!-- User Profile Avatar & Menu -->
                    <div class="flex items-center gap-2 pl-1 border-l border-zinc-200 dark:border-zinc-800">
                        <button type="button" class="flex items-center gap-2 p-1 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                            <div class="h-7 w-7 rounded-full bg-gradient-to-tr from-purple-500 to-indigo-600 flex items-center justify-center text-white text-xs font-semibold ring-2 ring-white dark:ring-zinc-800">
                                AR
                            </div>
                            <span class="hidden lg:block text-xs font-semibold text-zinc-800 dark:text-zinc-200">Alex R.</span>
                            <svg class="hidden sm:block h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    <!-- Footer Elegante -->
    <footer class="mt-16 border-t border-zinc-200/80 bg-white py-8 text-xs text-zinc-500 dark:border-zinc-800/80 dark:bg-zinc-900/60 dark:text-zinc-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 font-medium text-emerald-600 dark:text-emerald-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Sistemas VCS operativos
                </span>
                <span class="text-zinc-300 dark:text-zinc-700">•</span>
                <span>CodeFlow Platform v1.0</span>
            </div>

            <div class="flex items-center gap-6">
                <a href="#api" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">API Docs</a>
                <a href="#vcs" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">VcsService</a>
                <a href="https://github.com/AlvFerMon/codeflow" target="_blank" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">GitHub Repo</a>
                <span class="text-zinc-400 dark:text-zinc-500">Hecho con Laravel & Tailwind</span>
            </div>
        </div>
    </footer>

</body>
</html>
