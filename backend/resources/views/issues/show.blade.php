@php($L = \App\Http\Controllers\Web\Collab\CollabLabels::class)
<x-app-layout :title="'#'.$issue->number.' '.$issue->title">
    @include('repositories._header', ['repository' => $repository, 'active' => 'issues'])

    {{-- Cabecera --}}
    <div class="mb-6 border-b border-zinc-200 dark:border-zinc-800 pb-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h1 class="min-w-0 break-words text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $issue->title }} <span class="font-normal text-zinc-500 dark:text-zinc-400">#{{ $issue->number }}</span>
            </h1>
            @can('create', [\App\Models\Issue::class, $repository])
                <x-button variant="secondary" :href="route('issues.create', $repository)">Nueva issue</x-button>
            @endcan
        </div>
        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <x-collab-issue-status :status="$issue->status" class="px-2.5 py-1 text-sm" />
            <span><span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $issue->author?->name ?? 'Usuario eliminado' }}</span> abrió esta issue
                <time title="{{ $issue->created_at->format('d/m/Y H:i') }}">{{ $issue->created_at->diffForHumans() }}</time>
                · {{ $conversation['total'] }} {{ $conversation['total'] === 1 ? 'comentario' : 'comentarios' }}</span>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        {{-- Conversación --}}
        <div class="min-w-0 space-y-4">
            <article class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                <header class="flex flex-wrap items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 px-4 py-2 text-sm">
                    <x-avatar :user="$issue->author" size="sm" />
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $issue->author?->name ?? 'Usuario eliminado' }}</span>
                    <span class="text-zinc-500 dark:text-zinc-400">· descripción</span>
                </header>
                <div class="px-4 py-3">
                    <x-markdown :text="$issue->description" />
                </div>
            </article>

            @can('update', $issue)
                <details class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <summary class="cursor-pointer px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100">Editar título y descripción</summary>
                    <form method="POST" action="{{ route('issues.update', [$repository, $issue->number]) }}" class="space-y-3 border-t border-zinc-200 dark:border-zinc-800 p-4">
                        @csrf @method('PUT')
                        <x-input label="Título" name="title" :value="$issue->title" required maxlength="200" />
                        <x-textarea label="Descripción" name="description" :value="$issue->description" rows="6" />
                        <x-button>Guardar cambios</x-button>
                    </form>
                </details>
            @endcan

            @include('partials._conversation-nav', ['conversation' => $conversation, 'url' => route('issues.show', [$repository, $issue->number])])
            @if ($timeline->isNotEmpty())
                <div class="relative space-y-4 before:absolute before:inset-y-0 before:left-7 before:w-px before:bg-zinc-200 dark:before:bg-zinc-700">
                    @foreach ($timeline as $entry)
                        <div class="relative">
                            @if ($entry['type'] === 'comment')
                                <x-collab-comment :comment="$entry['item']" :role="$roles[$entry['item']->user_id] ?? null" :anchor="'comment-'.$entry['item']->id" />
                            @else
                                @include('issues._event', ['log' => $entry['item'], 'users' => $users])
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @can('comment', $issue)
                <form method="POST" action="{{ route('issues.comment', [$repository, $issue->number]) }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
                    @csrf
                    <x-textarea label="Añadir un comentario" name="body" rows="4" required placeholder="Escribe tu comentario…" />
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Admite Markdown.
                            @if (auth()->user()->roleIn($repository->project) === \App\Support\Roles::OBSERVER)
                                <span class="font-medium text-amber-700 dark:text-amber-300">Como observador, tu comentario avisará al equipo con una alerta.</span>
                            @endif
                        </p>
                        <x-button>Comentar</x-button>
                    </div>
                </form>
            @else
                @guest
                    <p class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <a href="{{ route('login') }}" class="font-medium text-purple-600 dark:text-purple-400 hover:underline">Inicia sesión</a> como miembro del proyecto para comentar.
                    </p>
                @endguest
            @endcan
        </div>

        {{-- Barra lateral --}}
        <aside class="space-y-4">
            @can('update', $issue)
                <x-card title="Detalles">
                    <form method="POST" action="{{ route('issues.update', [$repository, $issue->number]) }}" class="space-y-3 p-4">
                        @csrf @method('PUT')
                        <x-select label="Estado" name="status" :options="$L::ISSUE_STATUSES" :value="$issue->status" />
                        <x-select label="Prioridad" name="priority" :options="$L::PRIORITIES" :value="$issue->priority" />
                        <x-select label="Asignada a" name="assignee_id" :options="$members" :value="$issue->assignee_id" placeholder="Sin asignar" />
                        <x-input label="Etiquetas" name="labels" :value="implode(', ', $issue->labels ?? [])" help="Separadas por comas." />
                        <x-button class="w-full">Guardar</x-button>
                    </form>
                </x-card>
            @else
                <x-card title="Detalles">
                    <dl class="space-y-3 p-4 text-sm">
                        <div><dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Prioridad</dt><dd class="mt-1"><x-collab-priority :priority="$issue->priority" /></dd></div>
                        <div><dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Asignada a</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                @if ($issue->assignee)
                                    <x-avatar :user="$issue->assignee" size="sm" /> {{ $issue->assignee->name }}
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">Nadie</span>
                                @endif
                            </dd></div>
                        <div><dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Etiquetas</dt>
                            <dd class="mt-1 flex flex-wrap gap-1">
                                @forelse ($issue->labels ?? [] as $label)
                                    <x-collab-label :label="$label" />
                                @empty
                                    <span class="text-zinc-500 dark:text-zinc-400">Ninguna</span>
                                @endforelse
                            </dd></div>
                    </dl>
                </x-card>
            @endcan

            <x-card title="Pull requests vinculados">
                @forelse ($issue->pullRequests as $pull)
                    <a href="{{ route('pulls.show', [$repository, $pull->number]) }}" class="flex items-start gap-2 border-b border-zinc-100 dark:border-zinc-800 px-4 py-2 text-sm last:border-0 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                        <x-collab-status-icon type="pull" :status="$pull->status" class="mt-0.5 h-4 w-4" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-zinc-800 dark:text-zinc-200"><span class="text-zinc-500 dark:text-zinc-400">#{{ $pull->number }}</span> {{ $pull->title }}</span>
                            <x-collab-pr-status :status="$pull->status" class="mt-1" />
                        </span>
                    </a>
                @empty
                    <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">Ningún pull request vincula esta issue.</p>
                @endforelse
            </x-card>

            @can('delete', $issue)
                <form method="POST" action="{{ route('issues.destroy', [$repository, $issue->number]) }}"
                    data-confirm-title="Eliminar la issue #{{ $issue->number }}" data-confirm-button="Eliminar issue" data-confirm="Se borrarán la issue, sus comentarios y su historial. Esta acción no se puede deshacer.">
                    @csrf @method('DELETE')
                    <x-button variant="danger" class="w-full">Eliminar issue</x-button>
                </form>
            @endcan
        </aside>
    </div>
</x-app-layout>
