{{-- Cabecera común de un proyecto con sus pestañas. Variables: $project, $active (overview|issues|pulls|activity|members|settings) --}}
@php
    $viewer = auth()->user();
    $myRole = $viewer?->roleIn($project);
    $repoIds = \App\Models\Repository::select('id')->where('project_id', $project->id);
    $openIssues = \App\Models\Issue::whereIn('repository_id', $repoIds)->whereIn('status', \App\Http\Controllers\Web\Collab\CollabLabels::ISSUE_OPEN_STATES)->count();
    $openPulls = \App\Models\PullRequest::whereIn('repository_id', $repoIds)->whereIn('status', \App\Models\PullRequest::OPEN_STATES)->count();
    $tabs = array_filter([
        'overview' => ['Resumen', route('projects.show', $project), null],
        'issues' => ['Issues', route('projects.issues', $project), $openIssues],
        'pulls' => ['Pull requests', route('projects.pulls', $project), $openPulls],
        'activity' => ['Actividad', route('projects.activity', $project), null],
        'members' => ['Miembros', route('members.index', $project), null],
        'settings' => $viewer?->can('update', $project) ? ['Configuración', route('projects.edit', $project), null] : null,
    ]);
@endphp
<div class="mb-6">
    <nav class="text-xs text-zinc-500 dark:text-zinc-400" aria-label="Ruta">
        <a href="{{ route('projects.index') }}" class="hover:text-zinc-900 hover:underline dark:hover:text-white">Proyectos</a>
        <span aria-hidden="true">/</span>
    </nav>
    <div class="mt-1 flex flex-wrap items-center gap-2">
        <h1 class="break-words text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">{{ $project->name }}</h1>
        @if ($myRole)
            <x-account-role-badge :role="$myRole" />
        @elseif ($viewer?->isAdmin())
            <x-badge variant="amber">Acceso como admin</x-badge>
        @endif
    </div>
    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
        Propietario: {{ $project->owner?->name ?? '—' }} · Creado {{ $project->created_at?->diffForHumans() }}
    </p>
    <x-tabs class="mt-4" label="Secciones del proyecto" :active="$active" :items="collect($tabs)->map(fn ($tab) => ['label' => $tab[0], 'url' => $tab[1], 'count' => $tab[2] ?: null])->all()" />
</div>
