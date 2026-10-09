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
    <nav class="mt-4 flex gap-1 overflow-x-auto border-b border-zinc-200 text-sm dark:border-zinc-800" aria-label="Secciones del proyecto">
        @foreach ($tabs as $key => [$label, $url, $count])
            <a href="{{ $url }}" @if ($active === $key) aria-current="page" @endif
                @class(['-mb-px flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2 transition-colors',
                    'border-purple-500 font-medium text-zinc-900 dark:text-white' => $active === $key,
                    'border-transparent text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $active !== $key])>
                {{ $label }}
                @if ($count)
                    <span class="rounded-full bg-zinc-100 px-1.5 text-[11px] font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $count }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
