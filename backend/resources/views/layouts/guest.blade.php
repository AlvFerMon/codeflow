<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}CodeFlow</title>
    @include('layouts._theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative flex min-h-full flex-col items-center justify-center bg-zinc-50 px-4 py-12 font-sans text-zinc-900 antialiased selection:bg-purple-500 selection:text-white dark:bg-zinc-950 dark:text-zinc-100">
    {{-- Halo de color de fondo --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-0 h-72 bg-gradient-to-b from-purple-500/10 via-indigo-500/5 to-transparent dark:from-purple-500/15" aria-hidden="true"></div>

    <main class="relative w-full max-w-md">
        <a href="{{ route('home') }}" class="group mb-8 flex items-center justify-center gap-2.5" aria-label="CodeFlow, inicio">
            <span class="h-10 w-10 rounded-xl bg-gradient-to-tr from-purple-600 via-indigo-600 to-sky-500 p-0.5 shadow-sm transition-transform group-hover:scale-105">
                <span class="flex h-full w-full items-center justify-center rounded-[10px] bg-zinc-950">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"/></svg>
                </span>
            </span>
            <span class="text-xl font-bold tracking-tight text-zinc-950 dark:text-white">CodeFlow</span>
        </a>
        <div class="rounded-xl border border-zinc-200/80 bg-white p-8 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <x-flash />
            {{ $slot }}
        </div>
        <div class="mt-6 flex justify-center">
            @include('layouts._theme-switch')
        </div>
    </main>
</body>
</html>
