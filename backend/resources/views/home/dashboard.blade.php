<x-app-layout title="Inicio">
    @php
        $linkClass = 'text-sm font-semibold text-zinc-900 transition-colors hover:text-purple-600 dark:text-white dark:hover:text-purple-400';
    @endphp

    <div class="space-y-8">
        {{-- Cabecera --}}
        <div class="flex flex-col justify-between gap-4 border-b border-zinc-200/80 pb-6 md:flex-row md:items-center dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">Flujo de trabajo</h1>
                    <x-badge variant="purple" size="xs">Hola, {{ \Illuminate\Support\Str::limit(auth()->user()->name, 24) }}</x-badge>
                </div>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Tus repositorios, pull requests e issues de un vistazo.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <x-button variant="secondary" size="sm" :href="route('projects.index')">
                    <svg class="h-3.5 w-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
                    <span>Mis proyectos</span>
                </x-button>
                @unless (\App\Http\Controllers\Web\Mobile\MobileController::isPhone(request()))
                <x-button variant="secondary" size="sm" :href="route('explore')">
                    <svg class="h-3.5 w-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <span>Explorar</span>
                </x-button>
                @endunless
                @can('create', \App\Models\Project::class)
                    <x-button size="sm" :href="route('projects.create')">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        <span>Nuevo proyecto</span>
                    </x-button>
                @endcan
            </div>
        </div>

        {{-- Métricas --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-metric-card title="Repositorios" :value="$stats['repositories']" :change="$projectsTotal.' '.($projectsTotal === 1 ? 'proyecto' : 'proyectos')" accent="blue" :href="route('projects.index')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/></svg>
                </x-slot:icon>
                <x-slot:footer>
                    <span class="font-medium text-emerald-600 dark:text-emerald-400">● {{ $stats['public'] }} {{ $stats['public'] === 1 ? 'público' : 'públicos' }}</span> · {{ $stats['private'] }} {{ $stats['private'] === 1 ? 'privado' : 'privados' }}
                </x-slot:footer>
            </x-metric-card>

            <x-metric-card title="Pull requests" :value="$stats['pulls']" change="abiertas" accent="green">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                </x-slot:icon>
                <x-slot:footer>{{ $stats['pulls_approved'] }} {{ $stats['pulls_approved'] === 1 ? 'lista' : 'listas' }} para fusionar · {{ $stats['pulls_to_review'] }} esperan tu revisión</x-slot:footer>
            </x-metric-card>

            <x-metric-card title="Issues abiertas" :value="$stats['issues']" :change="$stats['issues_urgent'].' de prioridad alta'" accent="amber">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                </x-slot:icon>
                <x-slot:footer>{{ $stats['issues_resolved_week'] }} resueltas o cerradas en los últimos 7 días</x-slot:footer>
            </x-metric-card>

            <x-metric-card title="Commits" :value="$stats['commits_week']" change="últimos 7 días" accent="purple">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="3.25"/><path stroke-linecap="round" d="M2.25 12h6.5m6.5 0h6.5"/></svg>
                </x-slot:icon>
                <x-slot:footer><span class="font-mono text-purple-600 dark:text-purple-400">{{ $stats['commits_today'] }} hoy</span> · motor VcsService</x-slot:footer>
            </x-metric-card>
        </div>

        {{-- Repositorios recientes --}}
        <section id="repositorios" class="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col justify-between gap-3 border-b border-zinc-100 pb-5 sm:flex-row sm:items-center dark:border-zinc-800">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Repositorios recientes</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Ordenados por su último commit</p>
                </div>
                <a href="{{ route('projects.index') }}" class="text-xs font-semibold text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300">Ver proyectos ({{ $projectsTotal }}) →</a>
            </div>
            @forelse ($repositories as $repository)
                <div @class(['-mx-2 flex flex-col justify-between gap-3 rounded-lg px-2 py-4 transition-colors hover:bg-zinc-50/50 sm:flex-row sm:items-center dark:hover:bg-zinc-800/20', 'border-t border-zinc-100 dark:border-zinc-800' => ! $loop->first])>
                    <div class="min-w-0 space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('repositories.show', $repository) }}" class="{{ $linkClass }} break-all">
                                {{ $repository->project?->name }} / {{ $repository->name }}
                            </a>
                            @if ($repository->isPublic())
                                <x-badge variant="success" size="xs">Público</x-badge>
                            @else
                                <x-badge size="xs">Privado</x-badge>
                            @endif
                            <x-badge variant="purple" size="xs">
                                <x-code-icon name="branch" class="h-3 w-3" /> {{ $repository->default_branch }}
                            </x-badge>
                        </div>
                        @if ($repository->description)
                            <p class="line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $repository->description }}</p>
                        @endif
                        <p class="pt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            @if ($repository->last_commit_sha)
                                Último commit:
                                <a href="{{ route('commits.show', [$repository, $repository->last_commit_sha]) }}" class="font-mono text-zinc-700 hover:text-purple-600 dark:text-zinc-300 dark:hover:text-purple-400">{{ substr($repository->last_commit_sha, 0, 7) }}</a>
                                <time datetime="{{ $repository->last_commit_at?->toIso8601String() }}" title="{{ $repository->last_commit_at?->format('d/m/Y H:i') }}">{{ $repository->last_commit_at?->diffForHumans() }}</time>
                            @else
                                Sin commits todavía
                            @endif
                        </p>
                    </div>
                    <div class="shrink-0">
                        <x-button variant="secondary" size="sm" :href="route('repositories.show', $repository)">Explorar</x-button>
                    </div>
                </div>
            @empty
                <x-empty :title="$projectsTotal === 0 ? 'Todavía no participas en ningún proyecto' : 'Todavía no hay repositorios en tus proyectos'">
                    @if ($projectsTotal === 0)
                        Crea un proyecto desde el menú «Mis proyectos» o pide al propietario de uno que te dé acceso.
                    @else
                        Abre uno de tus proyectos y crea su primer repositorio.
                    @endif
                </x-empty>
            @endforelse
        </section>
    </div>
</x-app-layout>
