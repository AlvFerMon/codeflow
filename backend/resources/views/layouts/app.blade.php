<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}CodeFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 text-slate-800 antialiased">
    <header class="bg-slate-900 text-slate-100">
        <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                <span class="grid h-8 w-8 place-items-center rounded-md bg-indigo-500 font-mono text-sm text-white">&lt;/&gt;</span>
                CodeFlow
            </a>
            <nav class="flex items-center gap-1 text-sm">
                @auth
                    <a href="{{ route('projects.index') }}" @class(['rounded px-3 py-1.5 hover:bg-slate-800', 'bg-slate-800' => request()->routeIs('projects.*')])>Proyectos</a>
                @endauth
                <a href="{{ route('explore') }}" @class(['rounded px-3 py-1.5 hover:bg-slate-800', 'bg-slate-800' => request()->routeIs('explore')])>Explorar</a>
            </nav>
            <div class="ml-auto flex items-center gap-3 text-sm">
                @auth
                    @php($unread = auth()->user()->unreadNotifications()->count())
                    <a href="{{ route('notifications.index') }}" class="relative rounded p-1.5 hover:bg-slate-800" title="Notificaciones">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        @if ($unread > 0)
                            <span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[11px] font-semibold text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
                        @endif
                    </a>
                    <details class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded px-2 py-1 hover:bg-slate-800">
                            <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-700 text-xs font-semibold">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                            @if (auth()->user()->isAdmin())
                                <span class="rounded bg-amber-400/20 px-1.5 text-[11px] font-medium text-amber-300">admin</span>
                            @endif
                        </summary>
                        <div class="absolute right-0 z-20 mt-2 w-48 overflow-hidden rounded-md border border-slate-200 bg-white text-slate-700 shadow-lg">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-slate-50">Mi perfil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="block w-full px-4 py-2 text-left hover:bg-slate-50">Cerrar sesión</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}" class="rounded px-3 py-1.5 hover:bg-slate-800">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="rounded bg-indigo-500 px-3 py-1.5 font-medium text-white hover:bg-indigo-400">Registrarse</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        <x-flash />
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-7xl px-4 pb-8 text-xs text-slate-400">
        CodeFlow · TFG 2.º DAM
    </footer>
</body>
</html>
