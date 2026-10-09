@php
    $L = \App\Http\Controllers\Web\Collab\CollabLabels::class;
    $branchOptions = $branches->mapWithKeys(fn ($b) => [$b->name => $b->name])->all();
    $ready = $source && $target && $problem === null && ! $existing;
@endphp
<x-app-layout :title="'Nuevo pull request · '.$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'pulls'])

    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Nuevo pull request</h1>
    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Elige la rama con tus cambios (origen) y la rama en la que quieres fusionarlos (destino).</p>

    {{-- 1. Selección de ramas: se recarga la página para ver la comparación --}}
    <form method="GET" action="{{ route('pulls.create', $repository) }}"
        class="mt-4 flex flex-wrap items-end gap-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4">
        <div class="w-full sm:w-56">
            <x-select label="Destino (base)" name="target" :options="$branchOptions" :value="$target?->name" onchange="this.form.submit()" class="font-mono" />
        </div>
        <span class="hidden pb-2 text-zinc-500 dark:text-zinc-400 sm:block" aria-hidden="true">←</span>
        <div class="w-full sm:w-56">
            <x-select label="Origen (tus cambios)" name="source" :options="$branchOptions" :value="$source?->name" placeholder="Elige una rama…" onchange="this.form.submit()" class="font-mono" />
        </div>
        <x-button variant="secondary">Comparar</x-button>
    </form>

    @if ($branches->count() < 2)
        <x-card class="mt-4">
            <x-empty title="Hace falta al menos otra rama">Crea una rama, sube cambios en ella y vuelve aquí para proponer su fusión.</x-empty>
        </x-card>
    @elseif (! $source)
        <x-card class="mt-4">
            <x-empty title="Elige la rama origen">Se mostrarán los archivos que cambian antes de abrir el pull request.</x-empty>
        </x-card>
    @else
        @if ($problem)
            <div class="mt-4 rounded-lg border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm text-amber-900 dark:text-amber-300">{{ $problem }}</div>
        @endif
        @if ($existing)
            <div class="mt-4 rounded-lg border border-sky-200 dark:border-sky-500/30 bg-sky-50 dark:bg-sky-500/10 px-4 py-3 text-sm text-sky-900 dark:text-sky-300">
                Ya hay un pull request abierto entre estas ramas:
                <a href="{{ route('pulls.show', [$repository, $existing->number]) }}" class="font-medium underline">#{{ $existing->number }} {{ $existing->title }}</a>.
            </div>
        @endif

        {{-- 2. Datos del pull request --}}
        @if ($ready)
            <form method="POST" action="{{ route('pulls.store', $repository) }}" class="mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                @csrf
                <input type="hidden" name="source_branch_id" value="{{ $source->id }}">
                <input type="hidden" name="target_branch_id" value="{{ $target->id }}">
                <x-card title="Descripción del cambio">
                    <div class="space-y-4 p-4">
                        <x-input label="Título" name="title" required maxlength="200" :value="(string) \Illuminate\Support\Str::of($source->name)->afterLast('/')->replace(['-', '_'], ' ')->ucfirst()" />
                        <div>
                            <x-textarea label="Descripción" name="description" rows="6" placeholder="Qué cambia, por qué y cómo probarlo." />
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Admite Markdown.</p>
                        </div>
                    </div>
                </x-card>
                <div class="space-y-4">
                    <x-card title="Revisores">
                        <div class="space-y-2 p-4">
                            @forelse ($reviewers as $reviewer)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="reviewer_ids[]" value="{{ $reviewer->id }}" @checked(in_array($reviewer->id, old('reviewer_ids', [])))
                                        class="rounded border-zinc-300 dark:border-zinc-700 text-purple-600 dark:text-purple-400 focus:ring-purple-500">
                                    <x-avatar :user="$reviewer" size="sm" />
                                    <span>{{ $reviewer->name }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ mb_strtolower($L::ROLES[$reviewer->pivot->role] ?? '') }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">No hay otros colaboradores en el proyecto.</p>
                            @endforelse
                        </div>
                    </x-card>
                    <x-card title="Issues que resuelve">
                        <div class="max-h-64 space-y-2 overflow-y-auto p-4">
                            @forelse ($openIssues as $openIssue)
                                <label class="flex items-start gap-2 text-sm">
                                    <input type="checkbox" name="issue_ids[]" value="{{ $openIssue->id }}" @checked(in_array($openIssue->id, old('issue_ids', [])))
                                        class="mt-0.5 rounded border-zinc-300 dark:border-zinc-700 text-purple-600 dark:text-purple-400 focus:ring-purple-500">
                                    <span><span class="text-zinc-500 dark:text-zinc-400">#{{ $openIssue->number }}</span> {{ $openIssue->title }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">No hay issues abiertas.</p>
                            @endforelse
                            @if ($openIssues->isNotEmpty())
                                <p class="pt-1 text-xs text-zinc-500 dark:text-zinc-400">Al fusionar, las issues marcadas pasan a «Resuelta».</p>
                            @endif
                        </div>
                    </x-card>
                    <x-button class="w-full">Abrir pull request</x-button>
                </div>
            </form>
        @endif

        {{-- 3. Vista previa de los cambios --}}
        @if ($files)
            <h2 class="mb-3 mt-8 text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Cambios de <span class="font-mono text-sm">{{ $source->name }}</span> respecto a <span class="font-mono text-sm">{{ $target->name }}</span>
            </h2>
            <x-diff :files="$files" />
        @endif
    @endif
</x-app-layout>
