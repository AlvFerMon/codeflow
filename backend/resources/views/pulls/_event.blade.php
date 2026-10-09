{{-- Evento del historial de un PR (activity_logs). Variable: $log --}}
@php
    $props = $log->properties ?? [];
    [$icon, $tone] = match ($log->action) {
        'pull_request.merged' => ['merge', 'bg-purple-600 text-white'],
        'pull_request.closed' => ['close', 'bg-rose-600 text-white'],
        'pull_request.reopened' => ['open', 'bg-emerald-600 text-white'],
        default => ['edit', 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400'],
    };
    $fieldLabels = ['title' => 'el título', 'description' => 'la descripción', 'reviewers' => 'los revisores'];
@endphp
<div class="flex items-center gap-3 py-1 pl-4 text-sm text-zinc-600 dark:text-zinc-400">
    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full ring-4 ring-zinc-50 dark:ring-zinc-800 {{ $tone }}">
        @if ($icon === 'merge')
            <x-collab-status-icon type="pull" status="merged" class="h-3.5 w-3.5 !text-white" />
        @elseif ($icon === 'close')
            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="m4.5 4.5 7 7m0-7-7 7"/></svg>
        @elseif ($icon === 'open')
            <x-collab-status-icon type="pull" status="open" class="h-3.5 w-3.5 !text-white" />
        @else
            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.5 2.5l2 2-8 8H3.5v-2z"/></svg>
        @endif
    </span>
    <p class="min-w-0 flex-1">
        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $log->user?->name ?? 'Alguien' }}</span>
        @switch($log->action)
            @case('pull_request.merged')
                fusionó <span class="font-mono text-xs">{{ $props['from'] ?? '' }}</span> en <span class="font-mono text-xs">{{ $props['to'] ?? '' }}</span>
                @isset($props['sha'])
                    con el commit <span class="font-mono text-xs">{{ $props['sha'] }}</span>
                @endisset
                @break
            @case('pull_request.closed')
                cerró el pull request
                @break
            @case('pull_request.reopened')
                reabrió el pull request
                @break
            @case('pull_request.updated')
                editó {{ collect($props['fields'] ?? [])->map(fn ($f) => $fieldLabels[$f] ?? $f)->join(', ', ' y ') ?: 'el pull request' }}
                @break
            @default
                {{ $log->action }}
        @endswitch
        <time class="text-zinc-400 dark:text-zinc-500" title="{{ $log->created_at->format('d/m/Y H:i') }}">{{ $log->created_at->diffForHumans() }}</time>
    </p>
</div>
