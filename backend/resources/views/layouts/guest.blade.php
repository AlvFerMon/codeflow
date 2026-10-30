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
<body class="relative flex min-h-full flex-col items-center justify-center bg-[#F2F4FF] px-4 py-12 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">

    <main class="relative w-full max-w-md">
        <a href="{{ route('home') }}" class="group mb-8 flex items-center justify-center gap-2.5" aria-label="CodeFlow, inicio">
            <x-logo size="h-10 w-10" text="text-xl" />
        </a>
        <div class="ficha rounded-lg p-8">
            <x-flash />
            {{ $slot }}
        </div>
        <div class="mt-6 flex justify-center">
            @include('layouts._theme-switch')
        </div>
        <nav class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400" aria-label="Información legal">
            <a href="{{ route('legal', 'privacidad') }}" class="hover:underline">Privacidad</a>
            <a href="{{ route('legal', 'cookies') }}" class="hover:underline">Cookies</a>
            <a href="{{ route('legal', 'terminos') }}" class="hover:underline">Condiciones</a>
            <a href="{{ route('legal', 'aviso-legal') }}" class="hover:underline">Aviso legal</a>
        </nav>
    </main>
    @include('layouts._confirm-dialog')
</body>
</html>
