{{-- Cabecera común de un repositorio. Variables: $repository, $active (code|issues|pulls|commits|branches|settings) --}}
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-2 text-lg">
        @can('view', $repository->project)
            <a href="{{ route('projects.show', $repository->project) }}" class="text-purple-600 dark:text-purple-400 hover:underline">{{ $repository->project->name }}</a>
        @else
            <span class="text-zinc-500 dark:text-zinc-400">{{ $repository->project->name }}</span>
        @endcan
        <span class="text-zinc-400 dark:text-zinc-500">/</span>
        <a href="{{ route('repositories.show', $repository) }}" class="font-semibold text-zinc-900 dark:text-zinc-100 hover:underline">{{ $repository->name }}</a>
        <x-badge :color="$repository->isPublic() ? 'green' : 'slate'">{{ $repository->isPublic() ? 'Público' : 'Privado' }}</x-badge>
    </div>
    @if ($repository->description)
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $repository->description }}</p>
    @endif
    <nav class="mt-4 flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-800 text-sm">
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
                'border-purple-500 font-medium text-zinc-900 dark:text-zinc-100' => $active === $key,
                'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' => $active !== $key,
            ])>{{ $label }}</a>
        @endforeach
        @can('update', $repository)
            <a href="{{ route('repositories.settings', $repository) }}" @class([
                '-mb-px ml-auto border-b-2 px-3 py-2 whitespace-nowrap',
                'border-purple-500 font-medium text-zinc-900 dark:text-zinc-100' => $active === 'settings',
                'border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' => $active !== 'settings',
            ])>Configuración</a>
        @endcan
    </nav>
</div>
