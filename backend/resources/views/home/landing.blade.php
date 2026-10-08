<x-app-layout title="Inicio">
    <section class="overflow-hidden rounded-2xl bg-slate-900 px-6 py-12 text-white shadow-lg sm:px-12 sm:py-16">
        <div class="max-w-2xl">
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-500/20 px-3 py-1 text-xs font-medium text-indigo-200 ring-1 ring-inset ring-indigo-400/30">
                Control de versiones propio · cifrado en la base de datos
            </span>
            <h1 class="mt-5 text-3xl font-bold tracking-tight sm:text-4xl">Colabora en tu código con CodeFlow</h1>
            <p class="mt-4 text-base leading-7 text-slate-300">
                Organiza tu trabajo en proyectos, guarda el código en repositorios con ramas y commits,
                y coordina al equipo con issues y pull requests. Cada repositorio se cifra con su propia clave.
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <x-button :href="route('login')" class="px-5 py-2.5">Iniciar sesión</x-button>
                <x-button :href="route('register')" variant="secondary" class="px-5 py-2.5">Registrarse</x-button>
                <a href="{{ route('explore') }}" class="inline-flex items-center justify-center gap-1 rounded-md px-5 py-2.5 text-sm font-medium text-slate-200 hover:bg-slate-800">
                    Explorar repositorios públicos →
                </a>
            </div>
        </div>
    </section>

    <section class="mt-8 grid gap-4 md:grid-cols-3">
        @foreach ([
            ['Proyectos y roles', 'Agrupa tus repositorios en proyectos y decide quién es colaborador u observador. Solo los miembros ven lo privado.', 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z', 'bg-indigo-50 text-indigo-600'],
            ['Ramas, commits y diferencias', 'Sube archivos, crea ramas y revisa el historial con un visor de diferencias línea a línea.', 'M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5', 'bg-emerald-50 text-emerald-600'],
            ['Issues y pull requests', 'Reporta tareas y errores, propone cambios, revisa el trabajo del equipo y fusiona cuando esté aprobado.', 'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5', 'bg-amber-50 text-amber-600'],
        ] as [$title, $text, $icon, $color])
            <x-card class="p-5">
                <span class="grid h-10 w-10 place-items-center rounded-lg {{ $color }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </span>
                <h2 class="mt-4 font-semibold text-slate-900">{{ $title }}</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $text }}</p>
            </x-card>
        @endforeach
    </section>
</x-app-layout>
