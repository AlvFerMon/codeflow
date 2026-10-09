{{-- Fila de la lista de pulls. Variables: $pull, $repository (opcional), $showRepository (bool). --}}
<li class="flex gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
    <x-collab-status-icon type="pull" :status="$pull->status" class="mt-0.5 h-5 w-5" />
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <a href="{{ route('pulls.show', [$repository ?? $pull->repository, $pull->number]) }}" class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-purple-600 dark:hover:text-purple-400">
                <span class="text-zinc-500 dark:text-zinc-400">#{{ $pull->number }}</span> {{ $pull->title }}
            </a>
            <x-collab-pr-status :status="$pull->status" />
        </div>
        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
        @if ($showRepository ?? false)
            <span class="inline-flex items-center gap-1 font-medium text-zinc-700 dark:text-zinc-300"><x-code-icon name="folder" class="h-3.5 w-3.5" />{{ $pull->repository?->name }}</span>
        @endif
            <span class="inline-flex max-w-full items-center gap-1 font-mono text-[11px]">
                <span class="truncate rounded bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 text-zinc-700 dark:text-zinc-300">{{ $pull->source_branch_name }}</span>
                <span aria-label="hacia">→</span>
                <span class="truncate rounded bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 text-zinc-700 dark:text-zinc-300">{{ $pull->target_branch_name }}</span>
            </span>
            <span>de <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $pull->author?->name ?? 'usuario eliminado' }}</span>
                <time datetime="{{ $pull->created_at->toIso8601String() }}" title="{{ $pull->created_at->format('d/m/Y H:i') }}">{{ $pull->created_at->diffForHumans() }}</time></span>
        </div>
    </div>
    <div class="flex shrink-0 items-start gap-3 text-xs text-zinc-500 dark:text-zinc-400">
        @if ($pull->reviewers->isNotEmpty())
            <span class="flex -space-x-1.5" title="Revisores: {{ $pull->reviewers->pluck('name')->join(', ') }}">
                @foreach ($pull->reviewers->take(3) as $reviewer)
                    <x-avatar :user="$reviewer" size="sm" class="ring-2 ring-white dark:ring-zinc-900" />
                @endforeach
            </span>
        @endif
        @if ($pull->comments_count > 0)
            <span class="inline-flex items-center gap-1" title="{{ $pull->comments_count }} comentarios">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linejoin="round" d="M2.5 3.5h11v7h-6l-3 2.5v-2.5h-2z"/></svg>
                {{ $pull->comments_count }}
            </span>
        @endif
    </div>
</li>
