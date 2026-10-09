{{-- Páginas de error en español. No consulta la base de datos (puede fallar en un 500). --}}
@php
    $message = isset($exception) ? trim((string) $exception->getMessage()) : '';
    $generic = ['', 'Unauthorized', 'Forbidden', 'Not Found', 'Page Expired', 'Too Many Requests', 'Server Error', 'Service Unavailable', 'This action is unauthorized.'];
    // Solo se muestra el motivo de un 403 (las policies dan mensajes en español); en el resto el
    // texto técnico de la excepción no aporta nada al usuario (p. ej. «No query results for model…»).
    $detail = ($code ?? 500) === 403 && ! in_array($message, $generic, true) ? __($message) : null;
@endphp
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · CodeFlow</title>
    @include('layouts._icons')
    @include('layouts._theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col items-center justify-center bg-zinc-50 px-4 py-12 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <main class="w-full max-w-lg text-center">
        <a href="{{ url('/') }}" class="mb-8 inline-flex items-center gap-2.5" aria-label="CodeFlow, inicio">
            <x-logo size="h-10 w-10" text="text-xl" />
        </a>
        <div class="rounded-xl border border-zinc-200/80 bg-white p-8 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <p class="font-mono text-sm font-semibold text-purple-600 dark:text-purple-400">Error {{ $code }}</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ $title }}</h1>
            <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
            @if ($detail)
                <p class="mt-3 rounded-lg bg-zinc-100 px-3 py-2 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $detail }}</p>
            @endif
            <div class="mt-6 flex flex-col justify-center gap-2 sm:flex-row">
                <x-button variant="secondary" type="button" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">← Volver</x-button>
                <x-button variant="purple" :href="url('/')">Ir al inicio</x-button>
                {{ $actions ?? '' }}
            </div>
        </div>
    </main>
</body>
</html>
