{{-- Cabecera común de un repositorio. Variables: $repository, $active (code|issues|pulls|commits|branches|settings) --}}
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-2 text-lg">
        @can('view', $repository->project)
            <a href="{{ route('projects.show', $repository->project) }}" class="text-indigo-600 hover:underline">{{ $repository->project->name }}</a>
        @else
            <span class="text-slate-500">{{ $repository->project->name }}</span>
        @endcan
        <span class="text-slate-400">/</span>
        <a href="{{ route('repositories.show', $repository) }}" class="font-semibold text-slate-900 hover:underline">{{ $repository->name }}</a>
        <x-badge :color="$repository->isPublic() ? 'green' : 'slate'">{{ $repository->isPublic() ? 'Público' : 'Privado' }}</x-badge>
    </div>
    @if ($repository->description)
        <p class="mt-1 text-sm text-slate-600">{{ $repository->description }}</p>
    @endif
    <nav class="mt-4 flex gap-1 overflow-x-auto border-b border-slate-200 text-sm">
        @php
            $active = $active ?? 'code';
            $tabs = [
                'code' => ['Código', route('repositories.show', $repository)],
                'issues' => ['Issues', route('issues.index', $repository)],
                'pulls' => ['Pull requests', route('pulls.index', $repository)],
                'commits' => ['Commits', route('commits.index', $repository)],
                'branches' => ['Ramas', route('branches.index', $repository)],
            ];
        @endphp
        @foreach ($tabs as $key => [$label, $url])
            <a href="{{ $url }}" @class([
                '-mb-px border-b-2 px-3 py-2 whitespace-nowrap',
                'border-indigo-500 font-medium text-slate-900' => $active === $key,
                'border-transparent text-slate-600 hover:text-slate-900' => $active !== $key,
            ])>{{ $label }}</a>
        @endforeach
        @can('update', $repository)
            <a href="{{ route('repositories.settings', $repository) }}" @class([
                '-mb-px ml-auto border-b-2 px-3 py-2 whitespace-nowrap',
                'border-indigo-500 font-medium text-slate-900' => $active === 'settings',
                'border-transparent text-slate-600 hover:text-slate-900' => $active !== 'settings',
            ])>Configuración</a>
        @endcan
    </nav>
</div>
