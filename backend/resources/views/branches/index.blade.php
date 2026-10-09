<x-app-layout :title="'Ramas · '.$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'branches'])

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <x-card :title="$branches->total().' '.($branches->total() === 1 ? 'rama' : 'ramas')" class="min-w-0 overflow-hidden">
            @if ($rows->isEmpty())
                <x-empty title="El repositorio no tiene ramas" />
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($rows as $row)
                        @php
                            $branch = $row['branch'];
                            $head = $branch->headCommit;
                            $branchParam = $row['is_default'] ? null : $branch->name;
                        @endphp
                        <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('repositories.show', array_filter([$repository, 'branch' => $branchParam])) }}" class="inline-flex items-center gap-1.5 break-all rounded bg-purple-50 dark:bg-purple-500/10 px-2 py-0.5 font-mono text-sm font-medium text-purple-700 dark:text-purple-300 hover:underline">
                                        <x-code-icon name="branch" class="h-3.5 w-3.5" /> {{ $branch->name }}
                                    </a>
                                    @if ($row['is_default'])
                                        <x-badge color="green">por defecto</x-badge>
                                    @else
                                        <span class="font-mono text-xs text-zinc-500 dark:text-zinc-400" title="Commits por delante / por detrás de {{ $repository->default_branch }}">
                                            <span class="text-emerald-700 dark:text-emerald-300">↑{{ $row['ahead'] }}</span>
                                            <span class="text-rose-700 dark:text-rose-300">↓{{ $row['behind'] }}</span>
                                        </span>
                                    @endif
                                    @if ($row['open_prs'] > 0)
                                        <a href="{{ route('pulls.index', $repository) }}"><x-badge color="purple">{{ $row['open_prs'] }} PR {{ $row['open_prs'] === 1 ? 'abierto' : 'abiertos' }}</x-badge></a>
                                    @endif
                                </div>
                                @if ($head)
                                    <p class="mt-1 flex min-w-0 items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        <a href="{{ route('commits.show', [$repository, $head->sha]) }}" class="shrink-0 font-mono text-xs text-zinc-500 dark:text-zinc-400 hover:text-purple-600 dark:hover:text-purple-400 hover:underline">{{ substr($head->sha, 0, 7) }}</a>
                                        <span class="truncate" title="{{ $head->message }}">{{ \Illuminate\Support\Str::before((string) $head->message, "\n") }}</span>
                                    </p>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $head->author?->name ?? 'Usuario eliminado' }} ·
                                        <time datetime="{{ $head->created_at->toIso8601String() }}" title="{{ $head->created_at->format('d/m/Y H:i') }}">{{ $head->created_at->diffForHumans() }}</time>
                                    </p>
                                @else
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Sin commits.</p>
                                @endif
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    Creada por {{ $branch->creator?->name ?? 'usuario eliminado' }} el {{ $branch->created_at->format('d/m/Y') }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1 sm:justify-end">
                                <x-button variant="ghost" class="px-2.5 py-1 text-xs" :href="route('commits.index', array_filter([$repository, 'branch' => $branchParam]))" title="Historial de la rama">
                                    <x-code-icon name="history" /> {{ $row['commits_count'] }} {{ $row['commits_count'] === 1 ? 'commit' : 'commits' }}
                                </x-button>
                                <x-button variant="ghost" class="px-2.5 py-1 text-xs" :href="route('repositories.show', array_filter([$repository, 'branch' => $branchParam]))">
                                    <x-code-icon name="code" /> Código
                                </x-button>
                                @can('push', $repository)
                                    @if ($row['is_default'])
                                    @elseif ($row['open_prs'] > 0)
                                        <span class="inline-flex cursor-not-allowed items-center px-2.5 py-1 text-zinc-300 dark:text-zinc-600" title="No se puede eliminar: tiene pull requests abiertos">
                                            <x-code-icon name="trash" /><span class="sr-only">No se puede eliminar: tiene pull requests abiertos</span>
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('branches.destroy', $branch) }}"
                                            data-confirm-title="Eliminar la rama {{ $branch->name }}" data-confirm-button="Eliminar rama"
                                            data-confirm="Los commits que solo estén en esta rama dejarán de ser accesibles. Esta acción no se puede deshacer.">
                                            @csrf
                                            @method('DELETE')
                                            <x-button variant="ghost" class="px-2.5 py-1 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10" title="Eliminar rama">
                                                <x-code-icon name="trash" /><span class="sr-only">Eliminar rama {{ $branch->name }}</span>
                                            </x-button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ul>
                <x-pagination :paginator="$branches" />
            @endif
        </x-card>

        <aside class="space-y-4">
            @can('push', $repository)
                <x-card title="Nueva rama">
                    <form method="POST" action="{{ route('branches.store', $repository) }}" class="space-y-3 p-4">
                        @csrf
                        <x-input name="name" label="Nombre" required maxlength="100" placeholder="feature/mi-cambio" class="font-mono"
                            help="Letras, números y . _ - /" />
                        <x-select name="from_branch_id" label="Desde" :options="$fromOptions" :value="$defaultId" />
                        <x-button class="w-full"><x-code-icon name="branch" /> Crear rama</x-button>
                    </form>
                </x-card>
            @endcan
            <x-card>
                <div class="space-y-2 p-4 text-xs text-zinc-500 dark:text-zinc-400">
                    <p>Una rama es una línea de trabajo independiente: apunta a su último commit.</p>
                    <p>La rama por defecto (<span class="font-mono">{{ $repository->default_branch }}</span>) no se puede eliminar, ni tampoco las ramas con pull requests abiertos.</p>
                </div>
            </x-card>
        </aside>
    </div>
</x-app-layout>
