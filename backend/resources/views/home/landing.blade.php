<x-app-layout title="Inicio">
    @php
        $stampDate = fn ($date) => $date->isToday() ? 'Hoy '.$date->format('H:i') : ($date->isYesterday() ? 'Ayer '.$date->format('H:i') : $date->translatedFormat('j M'));
    @endphp

    <section class="grid items-center gap-10 py-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,30rem)] lg:py-12">
        <div class="max-w-xl">
            <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight text-zinc-950 sm:text-5xl dark:text-white">Colabora en tu código con CodeFlow</h1>
            <p class="mt-5 text-lg leading-8 text-zinc-600 dark:text-zinc-400">
                Organiza tu trabajo en proyectos, guarda el código en repositorios con ramas y commits,
                y coordina al equipo con issues y pull requests. Todo desde el navegador, sin terminal.
                Cada repositorio se cifra con su propia clave.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <x-button :href="route('register')" size="lg">Crear una cuenta</x-button>
                <x-button :href="route('login')" variant="secondary" size="lg">Iniciar sesión</x-button>
            </div>
            <form action="{{ route('explore') }}" method="GET" role="search" class="mt-8 flex max-w-lg gap-2 border-t border-zinc-300 pt-6 dark:border-zinc-800">
                <label for="landing-q" class="sr-only">Buscar repositorios públicos</label>
                <input id="landing-q" type="search" name="q" maxlength="100" placeholder="Busca código público por nombre o proyecto…"
                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm placeholder-zinc-500 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:placeholder-zinc-400">
                <x-button variant="secondary">Buscar</x-button>
            </form>
            <a href="{{ route('explore') }}" class="mt-3 inline-block text-sm font-bold text-purple-600 hover:text-purple-700 dark:text-purple-300">Explorar repositorios públicos</a>
        </div>

        {{-- Una ficha real: así queda registrado cada cambio --}}
        @if ($featured)
            <figure class="min-w-0">
                <div class="ficha rounded-lg">
                    <div class="border-b border-zinc-300 p-5 dark:border-zinc-800">
                        <p class="text-lg font-extrabold text-zinc-950 dark:text-white">
                            <a href="{{ route('repositories.show', $featured) }}" class="hover:text-purple-600 dark:hover:text-purple-300"><span class="font-semibold text-zinc-500 dark:text-zinc-400">{{ $featured->project?->name }} /</span> {{ $featured->name }}</a>
                        </p>
                        @if ($featured->description)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $featured->description }}</p>
                        @endif
                    </div>
                    <div class="rayado">
                        <div class="grid h-9 grid-cols-[minmax(0,1fr)_auto] items-end gap-3 px-5 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <span>Cambio · quién</span><span>Sellado</span>
                        </div>
                        @forelse ($featuredStamps as $commit)
                            <div class="grid h-9 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-5 text-sm">
                                <span class="truncate text-zinc-800 dark:text-zinc-300">{{ \Illuminate\Support\Str::of($commit->message)->before("\n") }} <span class="text-zinc-500 dark:text-zinc-400">· {{ \Illuminate\Support\Str::of($commit->author?->name ?? '')->before(' ') }}</span></span>
                                <time datetime="{{ $commit->created_at->toIso8601String() }}" @class(['sello sello-entra', 'sello-nuevo' => $loop->first]) style="animation-delay: {{ 200 + $loop->index * 90 }}ms">{{ $stampDate($commit->created_at) }}</time>
                            </div>
                        @empty
                            <p class="flex h-9 items-center px-5 text-sm text-zinc-500 dark:text-zinc-400">Todavía sin cambios.</p>
                        @endforelse
                        <div class="h-9"></div>
                    </div>
                </div>
                <figcaption class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">Cada cambio queda sellado con quién lo hizo y cuándo, como en la ficha de un libro de la biblioteca.</figcaption>
            </figure>
        @endif
    </section>

    {{-- Lo que se puede hacer: una ficha rayada en tres columnas --}}
    <section aria-labelledby="que-puedes" class="mt-10">
        <h2 id="que-puedes" class="border-b border-zinc-300 pb-2 text-sm font-extrabold uppercase tracking-wider text-zinc-700 dark:border-zinc-800 dark:text-zinc-300">Qué puedes hacer</h2>
        <dl class="grid divide-y divide-zinc-300 md:grid-cols-3 md:divide-x md:divide-y-0 dark:divide-zinc-800">
            @foreach ([
                ['Proyectos y roles', 'Agrupa tus repositorios en proyectos y decide quién es colaborador u observador. Solo los miembros ven lo privado.'],
                ['Ramas, commits y diferencias', 'Sube archivos o carpetas arrastrándolos, crea ramas y revisa el historial con un visor de diferencias línea a línea.'],
                ['Issues y pull requests', 'Reporta tareas y errores, propone cambios, revisa el trabajo del equipo y fusiona cuando esté aprobado.'],
            ] as [$title, $text])
                <div class="py-5 md:px-6 md:first:pl-0 md:last:pr-0">
                    <dt class="font-extrabold text-zinc-950 dark:text-white">{{ $title }}</dt>
                    <dd class="mt-1.5 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ $text }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="mt-12" aria-labelledby="publicos">
        <x-card>
            <x-slot:title><span id="publicos">Repositorios públicos recientes</span></x-slot:title>
            <x-slot:actions>
                <a href="{{ route('explore') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-purple-600 hover:text-purple-700 dark:text-purple-300">{{ $publicTotal === 1 ? 'Ver el repositorio' : 'Ver los '.$publicTotal }}<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg></a>
            </x-slot:actions>
            @if ($publicRepositories->isEmpty())
                <x-empty title="Todavía no hay repositorios públicos">Cuando alguien comparta su código aparecerá aquí.</x-empty>
            @else
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($publicRepositories as $repository)
                        <x-public-repo-row :repository="$repository" />
                    @endforeach
                </ul>
            @endif
        </x-card>
    </section>
</x-app-layout>
