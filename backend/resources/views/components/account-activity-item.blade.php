{{-- Una entrada de actividad ya preparada por ActivityPresenter, con su sello de fecha como en una ficha. --}}
@props(['item', 'projectName' => null, 'projectUrl' => null])
@php
    // Tinta según lo que ha pasado: el código en amatista, el trabajo del equipo en fucsia, la gestión en gris
    $ink = match ($item['category']) {
        'commit', 'branch', 'repository' => 'text-purple-600 dark:text-purple-300',
        'issue', 'pull_request' => 'text-rose-600 dark:text-rose-300',
        default => 'text-zinc-500 dark:text-zinc-400',
    };
    $letters = ['project' => 'P', 'member' => 'M', 'repository' => 'R', 'branch' => 'B', 'commit' => 'C', 'issue' => 'I', 'pull_request' => 'PR'];
    $labels = ['project' => 'Proyecto', 'member' => 'Miembro', 'repository' => 'Repositorio', 'branch' => 'Rama', 'commit' => 'Commit', 'issue' => 'Issue', 'pull_request' => 'Pull request'];
    $time = $item['time'];
    $stamp = $time ? ($time->isToday() ? 'Hoy '.$time->format('H:i') : ($time->isYesterday() ? 'Ayer '.$time->format('H:i') : $time->translatedFormat('j M'))) : null;
@endphp
<li {{ $attributes->merge(['class' => 'flex items-start gap-3 px-4 py-3']) }}>
    <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-[4px] border-[1.5px] border-current text-[10px] font-extrabold {{ $ink }}" title="{{ $labels[$item['category']] ?? '' }}">
        {{ $letters[$item['category']] ?? '·' }}<span class="sr-only">{{ $labels[$item['category']] ?? '' }}</span>
    </span>
    <div class="min-w-0 flex-1 text-sm leading-5 text-zinc-700 dark:text-zinc-300">
        <p class="break-words">{{ $item['sentence'] }}</p>
        @if ($projectName)
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                @if ($projectUrl)<a href="{{ $projectUrl }}" class="hover:underline">{{ $projectName }}</a>@else{{ $projectName }}@endif
            </p>
        @endif
    </div>
    @if ($stamp)
        <time datetime="{{ $time->toIso8601String() }}" title="{{ $time->format('d/m/Y H:i') }} ({{ $time->diffForHumans() }})" class="sello mt-0.5 shrink-0">{{ $stamp }}</time>
    @endif
</li>
