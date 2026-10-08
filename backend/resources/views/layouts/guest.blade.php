<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}CodeFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-slate-900 px-4 py-12 text-slate-800 antialiased">
    <div class="w-full max-w-md">
        <a href="{{ route('home') }}" class="mb-6 flex items-center justify-center gap-2 text-xl font-semibold text-white">
            <span class="grid h-9 w-9 place-items-center rounded-md bg-indigo-500 font-mono text-sm">&lt;/&gt;</span>
            CodeFlow
        </a>
        <div class="rounded-xl bg-white p-8 shadow-xl">
            <x-flash />
            {{ $slot }}
        </div>
    </div>
</body>
</html>
