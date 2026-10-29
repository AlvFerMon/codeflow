{{--
    Revisión con IA del pull request (opcional). Variables: $repository, $pr, $analysis (?AiAnalysis con findings).
    Mientras analiza, resources/js/ai-review.js vuelve a pedir este fragmento cada pocos segundos.
--}}
@php
    $inProgress = $analysis?->isInProgress() ?? false;
    $canAnalyze = auth()->user()?->can('analyze', $pr) ?? false;
    // El PR ha cambiado desde el análisis (solo tiene sentido mientras está abierto)
    $stale = $analysis && $analysis->status === 'done' && $pr->isOpen()
        && $pr->sourceBranch && $analysis->head_commit_id !== $pr->sourceBranch->head_commit_id;
    $severity = [
        'high' => ['Alta', 'danger'],
        'medium' => ['Media', 'warning'],
        'low' => ['Baja', 'zinc'],
    ];
    $categories = ['bug' => 'Error', 'security' => 'Seguridad', 'performance' => 'Rendimiento', 'style' => 'Estilo', 'maintainability' => 'Mantenibilidad'];
    $counts = $analysis?->findings?->countBy('severity') ?? collect();
@endphp
<section id="analisis-ia" data-ai-section @if ($inProgress) data-ai-poll="{{ route('pulls.ai.show', [$repository, $pr->number]) }}" @endif
    class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-800/40">
        <div class="flex items-center gap-2">
            <svg class="h-4 w-4 text-[#9448BC] dark:text-[#C687EB]" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></svg>
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Revisión con IA</h2>
            <x-badge size="xs">Opcional</x-badge>
        </div>
        @if ($canAnalyze && ! $inProgress)
            <form method="POST" action="{{ route('pulls.ai.store', [$repository, $pr->number]) }}">
                @csrf
                <x-button size="sm" variant="secondary">{{ $analysis ? 'Volver a analizar' : 'Analizar con IA' }}</x-button>
            </form>
        @endif
    </header>

    <div class="space-y-3 px-4 py-3 text-sm">
        @if (! $analysis)
            <p class="text-zinc-600 dark:text-zinc-400">
                Un modelo de IA local revisa los cambios y señala posibles errores, problemas de seguridad, de rendimiento y de estilo.
                El código no sale de vuestro servidor y no se guarda: solo se guardan los comentarios.
            </p>
            @unless ($canAnalyze)
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Solo los colaboradores y el propietario pueden lanzar el análisis.</p>
            @endunless
        @elseif ($inProgress)
            <div class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300" role="status">
                <svg class="h-5 w-5 animate-spin text-[#9448BC]" width="20" height="20" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <span>
                    @if ($analysis->status !== 'running')
                        En cola…
                    @elseif ($analysis->batches_total > 0)
                        Analizando: parte {{ min($analysis->batches_done + 1, $analysis->batches_total) }} de {{ $analysis->batches_total }}
                        ({{ $analysis->files_analyzed }} {{ $analysis->files_analyzed === 1 ? 'archivo' : 'archivos' }})…
                    @else
                        Preparando los cambios…
                    @endif
                    Esta sección se actualiza sola.
                </span>
            </div>
            @if ($analysis->status === 'running' && $analysis->batches_total > 0)
                @php
                    $pct = (int) round(100 * $analysis->batches_done / $analysis->batches_total);
                @endphp
                <div class="h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="Progreso del análisis">
                    <div class="h-full rounded-full bg-[#9448BC] transition-all duration-500" style="width: {{ max(4, $pct) }}%"></div>
                </div>
            @endif
            @if ($analysis->status === 'pending' && $analysis->created_at->lt(now()->subMinutes(2)))
                <p class="rounded-md bg-amber-500/10 px-3 py-2 text-xs text-amber-800 dark:text-amber-300">
                    Lleva un rato en cola. Comprueba que el procesador de análisis está arrancado: <code class="font-mono">docker compose --profile ia up -d</code>
                </p>
            @endif
        @elseif ($analysis->status === 'failed')
            <p class="rounded-md border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-rose-700 dark:text-rose-300" role="alert">{{ $analysis->error ?? 'El análisis no se pudo completar.' }}</p>
        @else
            @if ($stale)
                <p class="rounded-md bg-amber-500/10 px-3 py-2 text-xs text-amber-800 dark:text-amber-300">La rama ha cambiado desde este análisis: puede que algún comentario ya no aplique. Vuelve a analizar para actualizarlo.</p>
            @endif

            @if ($analysis->summary)
                <p class="whitespace-pre-line leading-6 text-zinc-700 dark:text-zinc-300">{{ $analysis->summary }}</p>
            @endif

            @if ($analysis->findings->isEmpty())
                <p class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400">
                    <svg class="h-4 w-4" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    La IA no ha encontrado problemas en los cambios.
                </p>
            @else
                <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <span>{{ $analysis->findings_count }} {{ $analysis->findings_count === 1 ? 'comentario' : 'comentarios' }}:</span>
                    @foreach ($severity as $key => [$label, $variant])
                        @if ($counts[$key] ?? 0)
                            <x-badge :variant="$variant" size="xs">{{ $counts[$key] }} {{ mb_strtolower($label) }}</x-badge>
                        @endif
                    @endforeach
                </div>

                <ul class="divide-y divide-zinc-100 rounded-md border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-800">
                    @foreach ($analysis->findings as $finding)
                        <li class="space-y-1 px-3 py-2.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :variant="$severity[$finding->severity][1] ?? 'zinc'" size="xs">{{ $severity[$finding->severity][0] ?? $finding->severity }}</x-badge>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $categories[$finding->category] ?? $finding->category }}</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $finding->title }}</span>
                            </div>
                            <p class="break-all font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $finding->path }}{{ $finding->line ? ':'.$finding->line : '' }}</p>
                            <p class="leading-6 text-zinc-700 dark:text-zinc-300">{{ $finding->explanation }}</p>
                            @if ($finding->suggestion)
                                <p class="leading-6 text-zinc-600 dark:text-zinc-400"><span class="font-medium text-zinc-800 dark:text-zinc-200">Sugerencia:</span> {{ $finding->suggestion }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif

        @if ($analysis && ! $inProgress)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Modelo {{ $analysis->model }}
                @if ($analysis->status === 'done')
                    · {{ $analysis->files_analyzed }} {{ $analysis->files_analyzed === 1 ? 'archivo revisado' : 'archivos revisados' }}
                    @if ($analysis->files_truncated) ({{ $analysis->files_truncated }} solo en parte por su tamaño) @endif
                    @if ($analysis->files_skipped) · {{ $analysis->files_skipped }} {{ $analysis->files_skipped === 1 ? 'omitido' : 'omitidos' }} @endif
                    @if ($analysis->started_at && $analysis->finished_at)
                        · tardó {{ $analysis->started_at->diffForHumans($analysis->finished_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) }}
                    @endif
                @endif
                · <time datetime="{{ ($analysis->finished_at ?? $analysis->created_at)->toIso8601String() }}">{{ ($analysis->finished_at ?? $analysis->created_at)->diffForHumans() }}</time>
                @if ($analysis->requester) · pedido por {{ $analysis->requester->name }} @endif
            </p>
            @if ($analysis->status === 'done' && ! empty($analysis->skipped_files))
                <details class="text-xs text-zinc-500 dark:text-zinc-400">
                    <summary class="cursor-pointer select-none hover:text-zinc-700 dark:hover:text-zinc-200">Ver archivos omitidos</summary>
                    <ul class="mt-2 max-h-56 space-y-1 overflow-y-auto rounded-md border border-zinc-200 px-3 py-2 dark:border-zinc-800">
                        @foreach ($analysis->skipped_files as $item)
                            <li class="flex flex-wrap justify-between gap-x-3">
                                <span class="break-all font-mono">{{ $item['path'] ?? '' }}</span>
                                <span>{{ \App\Ai\DiffBatcher::SKIP_REASONS[$item['reason'] ?? ''] ?? ($item['reason'] ?? '') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Son sugerencias automáticas y pueden equivocarse: revísalas antes de aplicarlas.</p>
        @endif
    </div>
</section>
