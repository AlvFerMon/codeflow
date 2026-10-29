@php
    $d = config('codeflow.downloads');
    $server = url('/');
    $platforms = [
        [
            'id' => 'windows',
            'title' => 'CodeFlow Desktop para Windows',
            'text' => 'Clona tus repositorios en una carpeta, mira qué has cambiado y haz commit, push y pull con un botón.',
            'url' => $d['windows'],
            'button' => 'Descargar para Windows',
            'meta' => 'Windows 10 y 11 · 64 bits · ~95 MB',
            'icon' => 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25',
            'color' => 'bg-[#FF3366]/10 text-[#FF3366]',
            'variant' => 'purple',
            'steps' => [
                'Abre CodeFlow-Setup.exe.',
                'Si Windows muestra «Windows protegió su PC», pulsa «Más información» → «Ejecutar de todas formas» (el instalador no está firmado).',
                'Inicia sesión con tu cuenta de CodeFlow.',
            ],
        ],
        [
            'id' => 'android',
            'title' => 'CodeFlow para Android',
            'text' => 'Consulta tu actividad, tus notificaciones y tus proyectos desde el móvil.',
            'url' => $d['android'],
            'button' => 'Descargar APK',
            'meta' => 'Android 8.0 o superior',
            'icon' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
            'color' => 'bg-[#2EC4B6]/10 text-[#2EC4B6]',
            'variant' => 'teal',
            'steps' => [
                'Descarga el APK desde el móvil.',
                'Ábrelo y, si te lo pide, permite «Instalar apps desconocidas» para el navegador.',
                'Inicia sesión con tu cuenta de CodeFlow.',
            ],
        ],
        [
            'id' => 'cli',
            'title' => 'Terminal (CLI)',
            'text' => 'Para quien prefiere la consola: clone, status, commit, push, pull y ramas desde cmd o PowerShell.',
            'url' => $d['cli'],
            'button' => 'Descargar codeflow.exe',
            'meta' => 'Windows · un único ejecutable · ~6 MB',
            'icon' => 'm6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z',
            'color' => 'bg-[#9448BC]/10 text-[#9448BC] dark:text-[#C687EB]',
            'variant' => 'amethyst',
            'steps' => [
                'Guarda codeflow.exe en una carpeta (p. ej. C:\Users\<tú>\bin) y añádela al PATH.',
                'codeflow login '.$server,
                'codeflow repos · codeflow clone <url del repositorio>',
            ],
        ],
    ];
@endphp
<x-app-layout title="Descargas">
    <div class="mb-8 border-b border-zinc-200/80 pb-6 dark:border-zinc-800">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">Descargas</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Usa CodeFlow fuera del navegador. Todas las apps se conectan a este servidor
            (<span class="font-mono text-zinc-700 dark:text-zinc-300">{{ $server }}</span>) con tu misma cuenta.
        </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        @foreach ($platforms as $p)
            <x-card class="flex flex-col p-5" id="{{ $p['id'] }}">
                <span class="grid h-11 w-11 place-items-center rounded-lg {{ $p['color'] }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $p['icon'] }}"/></svg>
                </span>
                <h2 class="mt-4 font-semibold text-zinc-900 dark:text-zinc-100">{{ $p['title'] }}</h2>
                <p class="mt-1 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ $p['text'] }}</p>

                <ol class="mt-4 list-decimal space-y-1.5 pl-5 text-xs leading-5 text-zinc-600 dark:text-zinc-400">
                    @foreach ($p['steps'] as $step)
                        <li class="break-words">{{ $step }}</li>
                    @endforeach
                </ol>

                <div class="mt-auto pt-5">
                    @if ($p['url'])
                        <x-button :variant="$p['variant']" size="lg" :href="$p['url']" class="w-full" rel="noopener">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            {{ $p['button'] }}
                        </x-button>
                    @else
                        <span class="flex w-full items-center justify-center rounded-lg border border-dashed border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Próximamente</span>
                    @endif
                    <p class="mt-2 text-center text-[11px] text-zinc-500 dark:text-zinc-400">Versión {{ $d['version'] }} · {{ $p['meta'] }}</p>
                </div>
            </x-card>
        @endforeach
    </div>

    <p class="mt-6 text-xs text-zinc-500 dark:text-zinc-400">
        Versiones anteriores y notas de cada versión en <a href="{{ $d['releases'] }}" target="_blank" rel="noopener" class="font-semibold text-[#FF3366] hover:underline">la página de versiones</a>.
    </p>
</x-app-layout>
