<x-mobile-layout title="Actividad" tab="activity">
    @if ($projects->count() > 1)
        <nav class="-mx-4 mb-3 flex gap-2 overflow-x-auto px-4 pb-1 text-sm" aria-label="Filtrar por proyecto">
            @foreach ([null => 'Todos'] + $projects->all() as $id => $name)
                <a href="{{ route('mobile.activity', $id ? ['project' => $id] : []) }}" @class([
                    'shrink-0 rounded-full border px-3 py-1',
                    'border-purple-600 bg-purple-600 font-medium text-white' => $filter === ($id ?: null),
                    'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300' => $filter !== ($id ?: null),
                ])>{{ $name }}</a>
            @endforeach
        </nav>
    @endif

    @if ($items->isEmpty())
        <x-card><x-empty title="Aún no hay actividad">Cuando tu equipo trabaje en los proyectos aparecerá aquí.</x-empty></x-card>
    @else
        <ul class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200/80 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
            @foreach ($items as $item)
                @php
                    $color = match ($item['category']) {
                        'commit', 'branch' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300',
                        'pull_request' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300',
                        'issue' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
                        default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
                    };
                    $actor = $item['actor']['name'] ?? 'Alguien';
                    $time = $item['created_at'] ? \Illuminate\Support\Carbon::parse($item['created_at']) : null;
                @endphp
                <li class="flex gap-3 px-4 py-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-sm font-bold {{ $color }}">{{ mb_strtoupper(mb_substr($actor, 0, 1)) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-5 break-words"><span class="font-semibold">{{ $actor }}</span> {{ $item['summary'] }}</p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                            @if ($item['project']['name'] ?? null)
                                <span class="rounded bg-zinc-100 px-1.5 py-0.5 dark:bg-zinc-800">{{ $item['project']['name'] }}</span>
                            @endif
                            @if ($item['repository'])
                                <span class="rounded bg-zinc-100 px-1.5 py-0.5 dark:bg-zinc-800">{{ $item['repository']['name'] }}</span>
                            @endif
                            @if ($item['branch'])
                                <span class="rounded bg-purple-50 px-1.5 py-0.5 font-mono text-purple-700 dark:bg-purple-500/15 dark:text-purple-300">{{ $item['branch'] }}{{ $item['target_branch'] ? ' → '.$item['target_branch'] : '' }}</span>
                            @endif
                            @if ($item['commit_sha'])
                                <span class="font-mono">{{ $item['commit_sha'] }}</span>
                            @endif
                            @if ($time)
                                <time datetime="{{ $time->toIso8601String() }}">{{ $time->diffForHumans() }}</time>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        @include('mobile._more', ['paginator' => $logs])
    @endif
</x-mobile-layout>
