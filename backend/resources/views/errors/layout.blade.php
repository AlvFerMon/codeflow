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
    @include('layouts._theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col items-center justify-center bg-zinc-50 px-4 py-12 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <main class="w-full max-w-lg text-center">
        <a href="{{ url('/') }}" class="mb-8 inline-flex items-center gap-2.5" aria-label="CodeFlow, inicio">
            <span class="h-10 w-10 rounded-xl bg-gradient-to-tr from-purple-600 via-indigo-600 to-sky-500 p-0.5 shadow-sm">
                <span class="flex h-full w-full items-center justify-center rounded-[10px] bg-zinc-950">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"/></svg>
                </span>
            </span>
            <span class="text-xl font-bold tracking-tight">CodeFlow</span>
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
