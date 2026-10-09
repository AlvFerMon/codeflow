{{-- Fila de repositorio público (Explorar y portada). $repository viene de Repository::forListing(). --}}
@props(['repository', 'counts' => false])
<li {{ $attributes->merge(['class' => 'flex flex-col justify-between gap-3 px-5 py-4 transition-colors hover:bg-zinc-50/60 sm:flex-row sm:items-center dark:hover:bg-zinc-800/30']) }}>
    <div class="min-w-0 space-y-1.5">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('repositories.show', $repository) }}" class="break-all text-sm font-semibold text-zinc-900 transition-colors hover:text-purple-600 dark:text-white dark:hover:text-purple-400">
                <span class="font-normal text-zinc-500 dark:text-zinc-400">{{ $repository->project?->name }} /</span> {{ $repository->name }}
            </a>
            <x-badge variant="success" size="xs">Público</x-badge>
            <x-badge variant="purple" size="xs"><x-code-icon name="branch" class="h-3 w-3" /> {{ $repository->default_branch }}</x-badge>
        </div>
        @if ($repository->description)
            <p class="line-clamp-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $repository->description }}</p>
        @endif
        <p class="flex flex-wrap items-center gap-x-4 gap-y-1 pt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
            @if ($repository->last_commit_sha)
                <span>Último commit <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ substr($repository->last_commit_sha, 0, 7) }}</span>
                    <time datetime="{{ $repository->last_commit_at?->toIso8601String() }}" title="{{ $repository->last_commit_at?->format('d/m/Y H:i') }}">{{ $repository->last_commit_at?->diffForHumans() }}</time></span>
            @else
                <span>Sin commits todavía</span>
            @endif
            @if ($counts)
                <span>{{ $repository->open_issues_count }} {{ $repository->open_issues_count === 1 ? 'issue abierta' : 'issues abiertas' }}</span>
                <span>{{ $repository->open_pulls_count }} {{ $repository->open_pulls_count === 1 ? 'pull request' : 'pull requests' }}</span>
            @endif
        </p>
    </div>
    <div class="shrink-0">
        <x-button variant="secondary" size="sm" :href="route('repositories.show', $repository)">Ver código</x-button>
    </div>
</li>
