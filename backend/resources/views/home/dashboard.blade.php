<x-app-layout title="Inicio">
    @php
        $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->before(' ')->limit(24);
        // Fecha del sello: como el tampón de la biblioteca, corta y en mayúsculas
        $stampDate = function ($date) {
            if (! $date) {
                return '—';
            }
            if ($date->isToday()) {
                return 'Hoy '.$date->format('H:i');
            }
            if ($date->isYesterday()) {
                return 'Ayer '.$date->format('H:i');
            }

            return $date->translatedFormat('j M');
        };
        $plural = fn (int $n, string $one, string $many) => $n.' '.($n === 1 ? $one : $many);
    @endphp

    {{-- Tutorial: lista «Primeros pasos» (solo cuentas nuevas, hasta completarla u ocultarla) --}}
    <x-onboarding-checklist />

    {{-- En móvil: saludo, «Te toca» y después las fichas; en escritorio «Te toca» va en su columna --}}
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_23.75rem] lg:gap-x-10">
            <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between lg:col-start-1 lg:row-start-1">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-zinc-950 dark:text-white">Hola, {{ $firstName }}</h1>
                    <p class="mt-2 max-w-2xl text-base text-zinc-600 dark:text-zinc-400" data-tour="resumen">
                        En tus proyectos hay
                        <a href="{{ route('projects.index') }}" class="font-bold text-zinc-950 underline decoration-zinc-300 hover:decoration-purple-500 dark:text-white dark:decoration-zinc-600">{{ $plural($stats['repositories'], 'repositorio', 'repositorios') }}</a>,
                        <strong class="text-zinc-950 dark:text-white">{{ $plural($stats['pulls'], 'pull request abierta', 'pull requests abiertas') }}</strong>
                        y <strong class="text-zinc-950 dark:text-white">{{ $plural($stats['issues'], 'issue abierta', 'issues abiertas') }}</strong>.
                        @if ($stats['commits_today'] > 0)
                            Hoy se han sellado <strong class="text-zinc-950 dark:text-white">{{ $plural($stats['commits_today'], 'cambio', 'cambios') }}</strong>.
                        @else
                            Hoy todavía no hay cambios.
                        @endif
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <x-button variant="secondary" size="sm" :href="route('projects.index')">Mis proyectos</x-button>
                    @unless (\App\Http\Controllers\Web\Mobile\MobileController::isPhone(request()))
                        <x-button variant="secondary" size="sm" :href="route('explore')">Explorar</x-button>
                    @endunless
                </div>
            </div>

            {{-- Lo que te toca hacer --}}
            <aside class="ficha min-w-0 self-start rounded-lg p-5 lg:col-start-2 lg:row-span-2 lg:row-start-1" aria-labelledby="te-toca" data-tour="te-toca">
                <h2 id="te-toca" class="border-b border-zinc-300 pb-2 text-sm font-extrabold uppercase tracking-wider text-zinc-700 dark:border-zinc-800 dark:text-zinc-300">Te toca</h2>
                <ul>
                    @foreach ($todo as $item)
                        @php
                            $model = $item['model'];
                            $href = $item['kind'] === 'pr' ? route('pulls.show', [$model->repository, $model->number]) : route('issues.show', [$model->repository, $model->number]);
                        @endphp
                        <li class="border-b border-zinc-200 dark:border-zinc-800">
                            <a href="{{ $href }}" class="group grid grid-cols-[2.5rem_minmax(0,1fr)] gap-3 py-3.5">
                                <span @class(['sello-estado', 'sello-hecho' => $item['state'] === 'done'])>
                                    <x-collab-status-icon :type="$item['kind'] === 'pr' ? 'pull' : 'issue'" :status="$model->status" class="h-4 w-4 text-current!" />
                                    <span class="sr-only">{{ $item['kind'] === 'pr' ? 'Pull request' : 'Issue' }}</span>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate font-bold text-zinc-900 group-hover:text-purple-600 dark:text-zinc-100 dark:group-hover:text-purple-300"><span class="font-mono text-zinc-500 dark:text-zinc-400">#{{ $model->number }}</span> {{ $model->title }}</span>
                                    <span class="block truncate text-sm text-zinc-600 dark:text-zinc-400">{{ $item['note'] }} · {{ $model->repository?->name }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="pt-4 text-sm text-zinc-600 dark:text-zinc-400">
                    @if ($todo->isEmpty())
                        No tienes nada pendiente. Cuando alguien te pida revisar una pull request o te asigne una issue, aparecerá aquí.
                    @else
                        Aquí aparecen las revisiones que te piden, tus issues y lo urgente de tus proyectos.
                    @endif
                </p>
            </aside>

            <div class="min-w-0 space-y-5 lg:col-start-1 lg:row-start-2">
                @forelse ($repositories as $repository)
                    @php
                        $commits = $stamps->get($repository->id, collect());
                    @endphp
                    <article class="ficha grid rounded-lg md:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]" @if ($loop->first) data-tour="ficha-repositorio" @endif>
                        <div class="min-w-0 p-5">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <h2 class="min-w-0 text-lg font-extrabold leading-tight text-zinc-950 dark:text-white">
                                    <a href="{{ route('repositories.show', $repository) }}" class="break-words hover:text-purple-600 dark:hover:text-purple-300">
                                        <span class="font-semibold text-zinc-500 dark:text-zinc-400">{{ $repository->project?->name }} /</span> {{ $repository->name }}
                                    </a>
                                </h2>
                                @if ($repository->isPublic())
                                    <x-badge variant="success" size="xs">Público</x-badge>
                                @else
                                    <x-badge variant="purple" size="xs">Privado</x-badge>
                                @endif
                            </div>
                            @if ($repository->description)
                                <p class="mt-1.5 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $repository->description }}</p>
                            @endif
                            <dl class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                <div class="flex items-center gap-1.5">
                                    <dt class="sr-only">Rama principal</dt>
                                    <x-code-icon name="branch" class="h-4 w-4 text-zinc-400" />
                                    <dd class="font-bold text-zinc-800 dark:text-zinc-200">{{ $repository->default_branch }}</dd>
                                </div>
                                @if ($repository->last_commit_sha)
                                    <div class="flex items-center gap-1.5">
                                        <dt class="sr-only">Último commit</dt>
                                        <x-code-icon name="commit" class="h-4 w-4 text-zinc-400" />
                                        <dd><a href="{{ route('commits.show', [$repository, $repository->last_commit_sha]) }}" class="font-mono text-zinc-800 hover:text-purple-600 dark:text-zinc-200 dark:hover:text-purple-300">{{ substr($repository->last_commit_sha, 0, 7) }}</a></dd>
                                    </div>
                                @endif
                            </dl>
                            <div class="mt-5 flex flex-wrap gap-2">
                                <x-button variant="secondary" size="sm" :href="route('repositories.show', $repository)">Abrir el código</x-button>
                                <x-button variant="ghost" size="sm" :href="route('commits.index', $repository)">Ver todos los cambios</x-button>
                            </div>
                        </div>

                        {{-- Columna rayada de sellos: quién cambió qué y cuándo --}}
                        <div @if ($loop->first) data-tour="sellos" @endif class="rayado min-w-0 border-t border-zinc-300 md:border-l md:border-t-0 dark:border-zinc-800">
                            <div class="grid h-9 grid-cols-[minmax(0,1fr)_auto] items-end gap-3 px-4 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <span>Cambio · quién</span><span>Sellado</span>
                            </div>
                            @forelse ($commits as $commit)
                                <a href="{{ route('commits.show', [$repository, $commit->sha]) }}"
                                   class="group grid h-9 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-4 text-sm hover:bg-purple-50/60 dark:hover:bg-purple-950/30"
                                   title="{{ $commit->author?->name ?? 'Autor desconocido' }} · {{ $commit->created_at->format('d/m/Y H:i') }}">
                                    <span class="flex min-w-0 gap-1">
                                        <span class="truncate text-zinc-800 group-hover:text-zinc-950 dark:text-zinc-300 dark:group-hover:text-white">{{ \Illuminate\Support\Str::of($commit->message)->before("\n") }}</span>
                                        {{-- Quién: fuera del recorte para que no desaparezca con mensajes largos --}}
                                        <span class="shrink-0 text-zinc-500 dark:text-zinc-400">· {{ \Illuminate\Support\Str::of($commit->author?->name ?? 'Usuario eliminado')->before(' ') }}</span>
                                    </span>
                                    <time datetime="{{ $commit->created_at->toIso8601String() }}" @class(['sello sello-entra', 'sello-nuevo' => $loop->first && $commit->created_at->isToday()]) style="animation-delay: {{ $loop->index * 70 }}ms">{{ $stampDate($commit->created_at) }}</time>
                                </a>
                            @empty
                                <p class="flex h-9 items-center px-4 text-sm text-zinc-500 dark:text-zinc-400">Sin cambios todavía: sube el primer archivo.</p>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <div class="ficha rounded-lg" data-tour="ficha-repositorio">
                        <x-empty :title="$projectsTotal === 0 ? 'Todavía no participas en ningún proyecto' : 'Todavía no hay repositorios en tus proyectos'"
                                 :action="$projectsTotal === 0 ? 'Crear un proyecto' : null" :href="$projectsTotal === 0 ? route('projects.create') : null">
                            @if ($projectsTotal === 0)
                                Crea un proyecto o pide a quien tenga uno que te invite. Cuando haya cambios, aparecerán aquí sellados con su fecha.
                            @else
                                Abre uno de tus proyectos y crea su primer repositorio.
                            @endif
                        </x-empty>
                    </div>
                @endforelse

                @if ($projectsTotal > 0 && $repositories->isNotEmpty())
                    <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-purple-600 hover:text-purple-700 dark:text-purple-300 dark:hover:text-purple-200">
                        {{ $projectsTotal === 1 ? 'Ver tu proyecto' : 'Ver tus '.$projectsTotal.' proyectos' }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                @endif
            </div>

    </div>
</x-app-layout>
