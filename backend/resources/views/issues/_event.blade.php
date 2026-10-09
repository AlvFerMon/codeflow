{{-- Evento del historial de una issue (activity_logs "issue.updated"). Variables: $log, $users (id => nombre) --}}
@php
    $L = \App\Http\Controllers\Web\Collab\CollabLabels::class;
    $changes = $log->properties['changes'] ?? [];
    $name = fn ($id) => $id ? ($users[$id] ?? 'usuario eliminado') : null;
@endphp
<div class="flex gap-3 py-1 pl-4 text-sm text-zinc-600 dark:text-zinc-400">
    <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 ring-4 ring-zinc-50 dark:ring-zinc-800">
        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.5 2.5l2 2-8 8H3.5v-2z"/></svg>
    </span>
    <div class="min-w-0 flex-1 space-y-1">
        @foreach ($changes as $field => $change)
            <p class="flex flex-wrap items-center gap-1">
                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $log->user?->name ?? 'Alguien' }}</span>
                @switch($field)
                    @case('status')
                        cambió el estado de <x-collab-issue-status :status="$change['from']" /> a <x-collab-issue-status :status="$change['to']" />
                        @break
                    @case('priority')
                        cambió la prioridad de <x-collab-priority :priority="$change['from']" /> a <x-collab-priority :priority="$change['to']" />
                        @break
                    @case('assignee_id')
                        @if ($change['to'])
                            asignó la issue a <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $name($change['to']) }}</span>
                        @else
                            quitó la asignación a <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $name($change['from']) }}</span>
                        @endif
                        @break
                    @case('labels')
                        @php
                            $from = is_array($change['from']) ? $change['from'] : (json_decode((string) $change['from'], true) ?: []);
                            $to = is_array($change['to']) ? $change['to'] : (json_decode((string) $change['to'], true) ?: []);
                        @endphp
                        @foreach (array_diff($to, $from) as $label)
                            añadió <x-collab-label :label="$label" />
                        @endforeach
                        @foreach (array_diff($from, $to) as $label)
                            quitó <x-collab-label :label="$label" />
                        @endforeach
                        @if (! array_diff($to, $from) && ! array_diff($from, $to))
                            reordenó las etiquetas
                        @endif
                        @break
                    @case('title')
                        cambió el título de <del class="text-zinc-500 dark:text-zinc-400">{{ $change['from'] }}</del> a <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $change['to'] }}</span>
                        @break
                    @case('description')
                        editó la descripción
                        @break
                    @default
                        modificó {{ $field }}
                @endswitch
                <time class="text-zinc-500 dark:text-zinc-400" title="{{ $log->created_at->format('d/m/Y H:i') }}">{{ $log->created_at->diffForHumans() }}</time>
            </p>
        @endforeach
    </div>
</div>
