<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    </main>
    @include('layouts._confirm-dialog')
</body>
</html>
