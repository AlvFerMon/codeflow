{{-- Una entrada de actividad ya preparada por ActivityPresenter. --}}
@props(['item', 'projectName' => null, 'projectUrl' => null])
@php
    $colors = [
        'project' => 'bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300',
        'member' => 'bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300',
        'repository' => 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300',
        'branch' => 'bg-sky-100 dark:bg-sky-500/20 text-sky-700 dark:text-sky-300',
        'commit' => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300',
        'issue' => 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300',
        'pull_request' => 'bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300',
    ];
    $letters = ['project' => 'P', 'member' => 'M', 'repository' => 'R', 'branch' => 'B', 'commit' => 'C', 'issue' => 'I', 'pull_request' => 'PR'];
@endphp
<li {{ $attributes->merge(['class' => 'flex gap-3 px-4 py-3']) }}>
    <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full text-[10px] font-bold {{ $colors[$item['category']] ?? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}" title="{{ $item['category'] }}">{{ $letters[$item['category']] ?? '•' }}</span>
    <div class="min-w-0 flex-1 text-sm leading-5 text-zinc-700 dark:text-zinc-300">
        <p class="break-words">{{ $item['sentence'] }}</p>
        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
            <time datetime="{{ $item['time']?->toIso8601String() }}" title="{{ $item['time']?->format('d/m/Y H:i') }}">{{ $item['time']?->diffForHumans() }}</time>
            @if ($projectName)
                · @if ($projectUrl)<a href="{{ $projectUrl }}" class="hover:underline">{{ $projectName }}</a>@else{{ $projectName }}@endif
            @endif
        </p>
    </div>
</li>
