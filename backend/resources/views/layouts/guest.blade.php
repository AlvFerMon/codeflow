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
<body class="relative flex min-h-full flex-col items-center justify-center bg-[#F2F4FF] px-4 py-12 font-sans text-zinc-900 antialiased selection:bg-[#FF3366] selection:text-white dark:bg-[#000501] dark:text-zinc-100">
    {{-- Halo de color de fondo --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-0 h-72 bg-gradient-to-b from-[#9448BC]/10 via-[#FF3366]/5 to-transparent dark:from-[#9448BC]/15" aria-hidden="true"></div>

    <main class="relative w-full max-w-md">
        <a href="{{ route('home') }}" class="group mb-8 flex items-center justify-center gap-2.5" aria-label="CodeFlow, inicio">
            <x-logo size="h-10 w-10" text="text-xl" />
        </a>
        <div class="rounded-xl border border-zinc-200/80 bg-white p-8 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <x-flash />
            {{ $slot }}
        </div>
        <div class="mt-6 flex justify-center">
            @include('layouts._theme-switch')
        </div>
    </main>
    @include('layouts._confirm-dialog')
</body>
</html>
