{{-- Fila de la lista de issues. Variables: $issue, $repository (opcional), $showRepository (bool). --}}
<li class="flex gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
    <x-collab-status-icon type="issue" :status="$issue->status" class="mt-0.5 h-5 w-5" />
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <a href="{{ route('issues.show', [$repository ?? $issue->repository, $issue->number]) }}" class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-purple-600 dark:hover:text-purple-400">
                <span class="text-zinc-500 dark:text-zinc-400">#{{ $issue->number }}</span> {{ $issue->title }}
            </a>
            @foreach ($issue->labels ?? [] as $label)
                <x-collab-label :label="$label" />
            @endforeach
        </div>
        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
        @if ($showRepository ?? false)
            <span class="inline-flex items-center gap-1 font-medium text-zinc-700 dark:text-zinc-300"><x-code-icon name="folder" class="h-3.5 w-3.5" />{{ $issue->repository?->name }}</span>
        @endif
            <x-collab-issue-status :status="$issue->status" />
            <x-collab-priority :priority="$issue->priority" />
            <span>abierta por <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $issue->author?->name ?? 'usuario eliminado' }}</span>
                <time datetime="{{ $issue->created_at->toIso8601String() }}" title="{{ $issue->created_at->format('d/m/Y H:i') }}">{{ $issue->created_at->diffForHumans() }}</time></span>
        </div>
    </div>
    <div class="flex shrink-0 items-start gap-3 text-xs text-zinc-500 dark:text-zinc-400">
        @if ($issue->assignee)
            <x-avatar :user="$issue->assignee" size="sm" />
        @endif
        @if ($issue->comments_count > 0)
            <span class="inline-flex items-center gap-1" title="{{ $issue->comments_count }} comentarios">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linejoin="round" d="M2.5 3.5h11v7h-6l-3 2.5v-2.5h-2z"/></svg>
                {{ $issue->comments_count }}
            </span>
        @endif
    </div>
</li>
