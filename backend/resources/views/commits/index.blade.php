<x-app-layout :title="'Commits · '.$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'commits'])

    @php($branchName = $branch?->name)

    <div class="mb-4 flex flex-wrap items-center gap-3">
        @if ($branch)
            <x-code-branch-select :action="route('commits.index', $repository)" :branches="$branches" :current="$branchName" :params="['path' => $path]" />
        @endif
        @if ($path !== '')
            <p class="flex min-w-0 flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                Historial de
                <a href="{{ route('repositories.blob', [$repository, 'branch' => $branchName, 'path' => $path]) }}" class="break-all font-mono text-purple-600 dark:text-purple-400 hover:underline">{{ $path }}</a>
                <a href="{{ route('commits.index', array_filter([$repository, 'branch' => $branchName !== $repository->default_branch ? $branchName : null])) }}" class="text-xs text-zinc-500 dark:text-zinc-400 hover:underline">(ver todos)</a>
            </p>
        @endif
        <span class="ml-auto text-sm text-zinc-500 dark:text-zinc-400">{{ $commits->total() }} {{ $commits->total() === 1 ? 'commit' : 'commits' }}</span>
    </div>

    @if ($commits->isEmpty())
        <x-card>
            <x-empty title="No hay commits">
                {{ $path !== '' ? 'Ningún commit de esta rama ha modificado este archivo.' : 'Esta rama todavía no tiene commits.' }}
            </x-empty>
        </x-card>
    @else
        <div class="space-y-6">
            @foreach ($days as $day => $dayCommits)
                <section>
                    <h2 class="mb-2 flex items-center gap-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                        <x-code-icon name="commit" class="h-4 w-4 text-zinc-400 dark:text-zinc-500" />
                        Commits del {{ \Illuminate\Support\Carbon::parse($day)->translatedFormat('j \d\e F \d\e Y') }}
                    </h2>
                    <x-card class="overflow-hidden">
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($dayCommits as $commit)
                                <li class="flex items-start gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                    <x-code-avatar :user="$commit->author" size="h-7 w-7 text-xs" class="mt-0.5 hidden sm:grid" />
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('commits.show', [$repository, $commit->sha]) }}" class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:text-purple-600 dark:hover:text-purple-400 hover:underline" title="{{ $commit->message }}">
                                            {{ \Illuminate\Support\Str::before((string) $commit->message, "\n") }}
                                        </a>
                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $commit->author?->name ?? 'Usuario eliminado' }}</span>
                                            · <time datetime="{{ $commit->created_at->toIso8601String() }}" title="{{ $commit->created_at->format('d/m/Y H:i') }}">{{ $commit->created_at->diffForHumans() }}</time>
                                            @if ($commit->second_parent_id)
                                                · <x-badge color="purple" class="py-0">merge</x-badge>
                                            @endif
                                        </p>
                                    </div>
                                    <a href="{{ route('commits.show', [$repository, $commit->sha]) }}" class="shrink-0 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-2 py-1 font-mono text-xs text-zinc-700 dark:text-zinc-300 hover:border-purple-300 dark:hover:border-purple-500/30 hover:text-purple-600 dark:hover:text-purple-400">{{ substr($commit->sha, 0, 7) }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                </section>
            @endforeach
        </div>

        @if ($commits->hasPages())
            <nav class="mt-6 flex items-center justify-center gap-2" aria-label="Paginación">
                @if ($commits->onFirstPage())
                    <span class="rounded-lg border border-zinc-200 dark:border-zinc-800 px-3 py-1.5 text-sm text-zinc-400 dark:text-zinc-500">Más recientes</span>
                @else
                    <x-button variant="secondary" class="py-1.5" :href="$commits->previousPageUrl()">Más recientes</x-button>
                @endif
                <span class="px-2 text-sm text-zinc-500 dark:text-zinc-400">Página {{ $commits->currentPage() }} de {{ $commits->lastPage() }}</span>
                @if ($commits->hasMorePages())
                    <x-button variant="secondary" class="py-1.5" :href="$commits->nextPageUrl()">Más antiguos</x-button>
                @else
                    <span class="rounded-lg border border-zinc-200 dark:border-zinc-800 px-3 py-1.5 text-sm text-zinc-400 dark:text-zinc-500">Más antiguos</span>
                @endif
            </nav>
        @endif
    @endif
</x-app-layout>
