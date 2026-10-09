<x-app-layout title="Mis proyectos">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isAdmin ? 'Proyectos' : 'Mis proyectos' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $projects->total() }} {{ $projects->total() === 1 ? 'proyecto' : 'proyectos' }}</p>
        </div>
        @can('create', \App\Models\Project::class)
            <x-button :href="route('projects.create')">+ Nuevo proyecto</x-button>
        @endcan
    </div>

    @if ($isAdmin)
        <div class="mb-4 rounded-lg border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
            Como administrador ves <strong>todos los proyectos</strong> de la plataforma, también aquellos de los que no eres miembro.
        </div>
    @endif

    @if ($projects->isEmpty())
        <x-card>
            <x-empty title="Todavía no participas en ningún proyecto">
                Crea uno nuevo o pide al propietario de un proyecto que te dé acceso.
                <div class="mt-4"><x-button :href="route('projects.create')">Crear proyecto</x-button></div>
            </x-empty>
        </x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)
                <x-account-project-card :project="$project" />
            @endforeach
        </div>
        @if ($projects->hasPages())
            <x-card class="mt-4"><x-account-pagination :paginator="$projects" /></x-card>
        @endif
    @endif
</x-app-layout>
