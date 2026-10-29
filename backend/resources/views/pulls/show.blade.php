@php
    $L = \App\Http\Controllers\Web\Collab\CollabLabels::class;
    $isOpen = $pr->isOpen();
    $branchChip = 'rounded bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 font-mono text-xs text-zinc-700 dark:text-zinc-300';
@endphp
<x-app-layout :title="'#'.$pr->number.' '.$pr->title">
    @include('repositories._header', ['repository' => $repository, 'active' => 'pulls'])

    {{-- Cabecera --}}
    <div class="mb-4">
        <h1 class="break-words text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
            {{ $pr->title }} <span class="font-normal text-zinc-500 dark:text-zinc-400">#{{ $pr->number }}</span>
        </h1>
        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <x-collab-pr-status :status="$pr->status" class="px-2.5 py-1 text-sm" />
            <span class="min-w-0">
                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $pr->author?->name ?? 'Usuario eliminado' }}</span>
                {{ $pr->status === 'merged' ? 'fusionó' : 'quiere fusionar' }}
                {{ $commitCount }} {{ $commitCount === 1 ? 'commit' : 'commits' }} de
                <span class="{{ $branchChip }}">{{ $pr->source_branch_name }}</span> en
                <span class="{{ $branchChip }}">{{ $pr->target_branch_name }}</span>
            </span>
        </div>
    </div>

    {{-- Pestañas --}}
    <x-tabs class="mb-6" label="Secciones del pull request" :active="$tab" :items="[
        'conversation' => ['label' => 'Conversación', 'url' => route('pulls.show', [$repository, $pr->number, 'tab' => 'conversation']), 'count' => $conversation['total'] + $pr->reviews->count()],
        'files' => ['label' => 'Archivos cambiados', 'url' => route('pulls.show', [$repository, $pr->number, 'tab' => 'files']), 'count' => count($files)],
    ]" />

    @if ($tab === 'files')
        @if (count($files) === 0)
            <x-card><x-empty title="No hay archivos cambiados">La rama origen no aporta cambios respecto a la de destino.</x-empty></x-card>
        @else
            <x-diff :files="$files" :diff-url="route('pulls.diff', [$repository, $pr->number])" />
        @endif
    @else
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="min-w-0 space-y-4">
                {{-- Descripción --}}
                <article class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <header class="flex flex-wrap items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 px-4 py-2 text-sm">
                        <x-avatar :user="$pr->author" size="sm" />
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $pr->author?->name ?? 'Usuario eliminado' }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400">abrió este pull request
                            <time title="{{ $pr->created_at->format('d/m/Y H:i') }}">{{ $pr->created_at->diffForHumans() }}</time></span>
                    </header>
                    <div class="px-4 py-3">
                        <x-markdown :text="$pr->description" />
                    </div>
                </article>

                {{-- Revisión con IA (opcional: solo si está activada) --}}
                @if (\App\Services\PullRequestAnalysisService::enabled())
                    @include('pulls._ai', ['analysis' => $pr->latestAiAnalysis()->with(['findings', 'requester:id,name'])->first()])
                @endif

                @if ($pr->isOpen())
                    @can('update', $pr)
                        <details class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900" @if ($errors->hasAny(['title', 'description'])) open @endif>
                            <summary class="cursor-pointer px-4 py-2.5 text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">Editar título, descripción y revisores</summary>
                            <form method="POST" action="{{ route('pulls.update', [$repository, $pr->number]) }}" class="space-y-3 border-t border-zinc-200 p-4 dark:border-zinc-800">
                                @csrf @method('PUT')
                                <x-input label="Título" name="title" :value="$pr->title" required maxlength="200" />
                                <x-textarea label="Descripción" name="description" :value="$pr->description" rows="5" />
                                @if ($reviewerOptions->isNotEmpty())
                                    <fieldset>
                                        <legend class="mb-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">Revisores</legend>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                            @foreach ($reviewerOptions as $candidate)
                                                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                                    <input type="checkbox" name="reviewer_ids[]" value="{{ $candidate->id }}" @checked($pr->reviewers->contains('id', $candidate->id))>
                                                    {{ $candidate->name }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                @endif
                                <x-button>Guardar cambios</x-button>
                            </form>
                        </details>
                    @endcan
                @endif

                {{-- Línea de tiempo --}}
                @include('partials._conversation-nav', ['conversation' => $conversation, 'url' => route('pulls.show', [$repository, $pr->number])])
                @if ($timeline->isNotEmpty())
                    <div class="relative space-y-4 before:absolute before:inset-y-0 before:left-7 before:w-px before:bg-zinc-200 dark:before:bg-zinc-700">
                        @foreach ($timeline as $entry)
                            <div class="relative">
                                @if ($entry['type'] === 'comment')
                                    @php $c = $entry['item']; @endphp
                                    <x-collab-comment :comment="$c" :role="$roles[$c->user_id] ?? null" :anchor="'comment-'.$c->id">
                                        @if ($c->path)
                                            <x-slot:meta>
                                                <span class="text-zinc-500 dark:text-zinc-400">comentó en <code class="rounded bg-zinc-200/70 dark:bg-zinc-700 px-1 font-mono text-xs text-zinc-800 dark:text-zinc-200">{{ $c->path }}</code>@if ($c->line) línea {{ $c->line }}@endif</span>
                                            </x-slot:meta>
                                        @endif
                                    </x-collab-comment>
                                @elseif ($entry['type'] === 'review')
                                    @php $r = $entry['item']; @endphp
                                    <x-collab-comment :comment="$r" :role="$roles[$r->user_id] ?? null" :anchor="'review-'.$r->id"
                                        :empty="match ($r->state) { 'approved' => 'Aprobó estos cambios.', default => 'Sin comentario.' }"
                                        @class([
                                            'border-l-4',
                                            'border-l-emerald-500' => $r->state === 'approved',
                                            'border-l-rose-500' => $r->state === 'changes_requested',
                                            'border-l-zinc-400 dark:border-l-zinc-600' => $r->state === 'commented',
                                        ])>
                                        <x-slot:meta>
                                            <span class="text-zinc-500 dark:text-zinc-400">revisó:</span>
                                            <x-collab-review-state :state="$r->state" />
                                        </x-slot:meta>
                                    </x-collab-comment>
                                @else
                                    @include('pulls._event', ['log' => $entry['item']])
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Estado de la fusión --}}
                @if ($pr->status === 'merged')
                    <div class="flex items-start gap-3 rounded-lg border border-purple-200 dark:border-purple-500/30 bg-purple-50 dark:bg-purple-500/10 p-4 text-sm text-purple-900 dark:text-purple-300">
                        <x-collab-status-icon type="pull" status="merged" class="mt-0.5 h-5 w-5" />
                        <p><span class="font-semibold">Fusionado</span> por {{ $mergedBy?->name ?? 'un usuario' }}
                            en <span class="{{ $branchChip }}">{{ $pr->target_branch_name }}</span>
                            @if ($pr->merged_at) <time title="{{ $pr->merged_at->format('d/m/Y H:i') }}">{{ $pr->merged_at->diffForHumans() }}</time>@endif.
                        </p>
                    </div>
                @elseif ($pr->status === 'closed')
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 text-sm">
                        <p class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                            <x-collab-status-icon type="pull" status="closed" class="h-5 w-5" />
                            Este pull request se cerró sin fusionar.
                        </p>
                        @can('close', $pr)
                            <form method="POST" action="{{ route('pulls.reopen', [$repository, $pr->number]) }}">
                                @csrf
                                <x-button variant="secondary">Reabrir</x-button>
                            </form>
                        @endcan
                    </div>
                @else
                    @php
                        $conflicts = $mergeability['conflicts'] ?? [];
                        $mergeable = $mergeability['mergeable'] ?? null;
                        $canMerge = $mergeDecision?->allowed() && $mergeable && $pr->status !== 'changes_requested';
                    @endphp
                    <section @class([
                        'overflow-hidden rounded-lg border bg-white dark:bg-zinc-900',
                        'border-rose-300 dark:border-rose-500/30' => $mergeable === false,
                        'border-emerald-300 dark:border-emerald-500/30' => $canMerge,
                        'border-zinc-300 dark:border-zinc-700' => $mergeable !== false && ! $canMerge,
                    ])>
                        <div class="divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                            {{-- Revisión --}}
                            <div class="flex items-start gap-3 px-4 py-3">
                                @switch($pr->status)
                                    @case('approved')
                                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                                        <p><span class="font-medium text-zinc-900 dark:text-zinc-100">Aprobado.</span> Los revisores han dado el visto bueno.</p>
                                        @break
                                    @case('changes_requested')
                                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500"></span>
                                        <p><span class="font-medium text-zinc-900 dark:text-zinc-100">Se han solicitado cambios.</span> Hay que resolverlos y volver a pedir revisión antes de fusionar.</p>
                                        @break
                                    @default
                                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-amber-500"></span>
                                        <p><span class="font-medium text-zinc-900 dark:text-zinc-100">Pendiente de revisión.</span> Un colaborador necesita al menos una aprobación para fusionarlo.</p>
                                @endswitch
                            </div>
                            {{-- Conflictos --}}
                            <div class="flex items-start gap-3 px-4 py-3">
                                @if ($mergeable === false)
                                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500"></span>
                                    <div>
                                        <p class="font-medium text-rose-800 dark:text-rose-300">Hay conflictos que impiden la fusión automática.</p>
                                        <p class="mt-1 text-zinc-600 dark:text-zinc-400">Resuélvelos en la rama <span class="{{ $branchChip }}">{{ $pr->source_branch_name }}</span>. Archivos en conflicto:</p>
                                        <ul class="mt-2 space-y-1">
                                            @foreach ($conflicts as $path)
                                                <li class="font-mono text-xs text-rose-800 dark:text-rose-300">{{ $path }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @elseif ($mergeable === true)
                                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                                    <p>No hay conflictos con <span class="{{ $branchChip }}">{{ $pr->target_branch_name }}</span>.</p>
                                @else
                                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-zinc-400"></span>
                                    <p class="text-zinc-600 dark:text-zinc-400">No se puede comprobar la fusión (alguna rama ya no existe o no tiene commits).</p>
                                @endif
                            </div>
                            {{-- Acciones --}}
                            @auth
                                <div class="flex flex-wrap items-center gap-3 bg-zinc-50 dark:bg-zinc-800/40 px-4 py-3">
                                    @if ($canMerge)
                                        <form method="POST" action="{{ route('pulls.merge', [$repository, $pr->number]) }}"
                                            data-confirm-title="Fusionar el pull request" data-confirm-button="Fusionar" data-confirm-variant="primary" data-confirm="Los cambios de {{ $pr->source_branch_name }} se incorporarán a {{ $pr->target_branch_name }} con un commit de fusión.">
                                            @csrf
                                            <x-button variant="success">Fusionar pull request</x-button>
                                        </form>
                                    @else
                                        <p class="flex-1 text-zinc-600 dark:text-zinc-400">
                                            @if ($pr->status === 'changes_requested')
                                                No se puede fusionar mientras haya cambios solicitados.
                                            @elseif ($mergeable === false)
                                                No se puede fusionar hasta resolver los conflictos.
                                            @elseif ($mergeDecision && $mergeDecision->denied())
                                                {{ $mergeDecision->message() }}
                                            @endif
                                        </p>
                                    @endif
                                    @can('close', $pr)
                                        <form method="POST" action="{{ route('pulls.close', [$repository, $pr->number]) }}" class="ml-auto"
                                            data-confirm-title="Cerrar sin fusionar" data-confirm-button="Cerrar pull request" data-confirm="El pull request se cerrará sin incorporar sus cambios. Podrás reabrirlo más adelante.">
                                            @csrf
                                            <x-button variant="secondary">Cerrar pull request</x-button>
                                        </form>
                                    @endcan
                                </div>
                            @endauth
                        </div>
                    </section>
                @endif

                {{-- Revisión --}}
                @if ($isOpen)
                    @can('review', $pr)
                        <form method="POST" action="{{ route('pulls.review', [$repository, $pr->number]) }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
                            @csrf
                            <h2 class="mb-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300">Revisar cambios</h2>
                            <x-textarea name="review_body" rows="3" placeholder="Resumen de tu revisión (obligatorio si pides cambios)…" aria-label="Resumen de la revisión" />
                            <fieldset class="mt-3 grid gap-2 sm:grid-cols-3">
                                <legend class="sr-only">Resultado de la revisión</legend>
                                @foreach (['approved' => ['Aprobar', 'Da el visto bueno para fusionar.'], 'changes_requested' => ['Solicitar cambios', 'Bloquea la fusión hasta resolverlos.'], 'commented' => ['Comentar', 'Opinión sin aprobar ni bloquear.']] as $value => [$label, $hint])
                                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-zinc-200 dark:border-zinc-800 p-2 text-sm has-[:checked]:border-purple-400 has-[:checked]:bg-purple-50 dark:has-[:checked]:border-purple-500/60 dark:has-[:checked]:bg-purple-500/10">
                                        <input type="radio" name="state" value="{{ $value }}" @checked(old('state', 'approved') === $value) class="mt-0.5 text-purple-600 dark:text-purple-400 focus:ring-purple-500">
                                        <span><span class="block font-medium text-zinc-800 dark:text-zinc-200">{{ $label }}</span><span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</span></span>
                                    </label>
                                @endforeach
                            </fieldset>
                            <div class="mt-3 flex justify-end"><x-button>Enviar revisión</x-button></div>
                        </form>
                    @endcan
                @endif

                {{-- Comentario --}}
                @can('comment', $pr)
                    <form method="POST" action="{{ route('pulls.comment', [$repository, $pr->number]) }}" class="rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
                        @csrf
                        <x-textarea label="Añadir un comentario" name="body" rows="4" required placeholder="Escribe tu comentario…" />
                        @can('commentOnCode', $pr)
                            <details class="mt-3 rounded-lg border border-zinc-200 dark:border-zinc-800" @if (old('path') || old('line')) open @endif>
                                <summary class="cursor-pointer px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">Comentar sobre una línea del código (opcional)</summary>
                                <div class="grid gap-3 border-t border-zinc-200 dark:border-zinc-800 p-3 sm:grid-cols-[1fr_8rem]">
                                    <x-select label="Archivo" name="path" :options="collect($files)->mapWithKeys(fn ($f) => [$f['path'] => $f['path']])->all()" placeholder="Comentario general" class="font-mono" />
                                    <x-input label="Línea" name="line" type="number" min="1" />
                                </div>
                            </details>
                        @endcan
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
                <x-card title="Revisores">
                    @forelse ($reviewStates as $row)
                        <div class="flex items-start gap-2 border-b border-zinc-100 dark:border-zinc-800 px-4 py-2 text-sm last:border-0">
                            <x-avatar :user="$row['user']" size="sm" />
                            <span class="min-w-0 flex-1">
                                <span class="block break-words text-zinc-800 dark:text-zinc-200">{{ $row['user']->name }}</span>
                                @if ($row['state'])
                                    <x-collab-review-state :state="$row['state']" class="mt-1" />
                                @else
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row['requested'] ? 'revisión pendiente' : '' }}</span>
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">Sin revisores asignados.</p>
                    @endforelse
                </x-card>

                <x-card title="Issues vinculadas">
                    @forelse ($pr->issues as $linked)
                        <a href="{{ route('issues.show', [$repository, $linked->number]) }}" class="flex items-start gap-2 border-b border-zinc-100 dark:border-zinc-800 px-4 py-2 text-sm last:border-0 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <x-collab-status-icon type="issue" :status="$linked->status" class="mt-0.5 h-4 w-4" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-zinc-800 dark:text-zinc-200"><span class="text-zinc-500 dark:text-zinc-400">#{{ $linked->number }}</span> {{ $linked->title }}</span>
                                <x-collab-issue-status :status="$linked->status" class="mt-1" />
                            </span>
                        </a>
                    @empty
                        <p class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">No resuelve ninguna issue.</p>
                    @endforelse
                </x-card>

                <x-card title="Ramas">
                    <dl class="space-y-3 p-4 text-sm">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Origen</dt>
                            <dd class="mt-1 flex items-center gap-2"><span class="{{ $branchChip }} break-all">{{ $pr->source_branch_name }}</span>
                                @unless ($pr->sourceBranch)<span class="text-xs text-zinc-500 dark:text-zinc-400">(eliminada)</span>@endunless</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Destino</dt>
                            <dd class="mt-1 flex items-center gap-2"><span class="{{ $branchChip }} break-all">{{ $pr->target_branch_name }}</span>
                                @unless ($pr->targetBranch)<span class="text-xs text-zinc-500 dark:text-zinc-400">(eliminada)</span>@endunless</dd>
                        </div>
                    </dl>
                </x-card>
            </aside>
        </div>
    @endif
</x-app-layout>
