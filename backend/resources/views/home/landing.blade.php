<x-app-layout title="Inicio">
    <section class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white px-6 py-12 shadow-xs sm:px-12 sm:py-16 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-gradient-to-tr from-purple-600/20 via-indigo-600/10 to-sky-500/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative max-w-2xl">
            <x-badge variant="purple" size="md">Control de versiones propio · cifrado en la base de datos</x-badge>
            <h1 class="mt-5 text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl dark:text-white">Colabora en tu código con CodeFlow</h1>
            <p class="mt-4 text-base leading-7 text-zinc-600 dark:text-zinc-400">
                Organiza tu trabajo en proyectos, guarda el código en repositorios con ramas y commits,
                y coordina al equipo con issues y pull requests. Cada repositorio se cifra con su propia clave.
            </p>
            <form action="{{ route('explore') }}" method="GET" role="search" class="mt-8 flex max-w-xl flex-col gap-2 sm:flex-row">
                <label for="landing-q" class="sr-only">Buscar repositorios públicos</label>
                <input id="landing-q" type="search" name="q" maxlength="100" placeholder="Busca código público: nombre, descripción o proyecto…"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm shadow-xs focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                <x-button size="lg">Buscar</x-button>
            </form>
            <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                <x-button :href="route('login')" size="lg">Iniciar sesión</x-button>
                <x-button :href="route('register')" variant="purple" size="lg">Registrarse</x-button>
                <x-button :href="route('explore')" variant="ghost" size="lg">Explorar repositorios públicos →</x-button>
            </div>
        </div>
    </section>

    <section class="mt-8" aria-labelledby="publicos">
        <x-card>
            <x-slot:title><span id="publicos">Repositorios públicos recientes</span></x-slot:title>
            <x-slot:actions>
                <a href="{{ route('explore') }}" class="text-xs font-semibold text-purple-600 hover:text-purple-700 dark:text-purple-400">Ver los {{ $publicTotal }} →</a>
            </x-slot:actions>
            @if ($publicRepositories->isEmpty())
                <x-empty title="Todavía no hay repositorios públicos">Cuando alguien comparta su código aparecerá aquí.</x-empty>
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($publicRepositories as $repository)
                        <x-public-repo-row :repository="$repository" />
                    @endforeach
                </ul>
            @endif
        </x-card>
    </section>

    <section class="mt-8 grid gap-4 md:grid-cols-3">
        @foreach ([
            ['Proyectos y roles', 'Agrupa tus repositorios en proyectos y decide quién es colaborador u observador. Solo los miembros ven lo privado.', 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z', 'bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400'],
            ['Ramas, commits y diferencias', 'Sube archivos, crea ramas y revisa el historial con un visor de diferencias línea a línea.', 'M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5', 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'],
            ['Issues y pull requests', 'Reporta tareas y errores, propone cambios, revisa el trabajo del equipo y fusiona cuando esté aprobado.', 'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5', 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400'],
        ] as [$title, $text, $icon, $color])
            <x-card class="p-5 transition-shadow hover:shadow-md">
                <span class="grid h-10 w-10 place-items-center rounded-lg {{ $color }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </span>
                <h2 class="mt-4 font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h2>
                <p class="mt-1 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
            </x-card>
        @endforeach
    </section>
</x-app-layout>
