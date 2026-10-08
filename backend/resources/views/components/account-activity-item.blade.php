{{-- Una entrada de actividad ya preparada por ActivityPresenter. --}}
@props(['item', 'projectName' => null, 'projectUrl' => null])
@php
    $colors = [
        'project' => 'bg-indigo-100 text-indigo-700',
        'member' => 'bg-purple-100 text-purple-700',
        'repository' => 'bg-slate-200 text-slate-700',
        'branch' => 'bg-sky-100 text-sky-700',
        'commit' => 'bg-emerald-100 text-emerald-700',
        'issue' => 'bg-amber-100 text-amber-800',
        'pull_request' => 'bg-rose-100 text-rose-700',
    ];
    $letters = ['project' => 'P', 'member' => 'M', 'repository' => 'R', 'branch' => 'B', 'commit' => 'C', 'issue' => 'I', 'pull_request' => 'PR'];
@endphp
<li {{ $attributes->merge(['class' => 'flex gap-3 px-4 py-3']) }}>
    <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full text-[10px] font-bold {{ $colors[$item['category']] ?? 'bg-slate-100 text-slate-600' }}" title="{{ $item['category'] }}">{{ $letters[$item['category']] ?? '•' }}</span>
    <div class="min-w-0 flex-1 text-sm leading-5 text-slate-700">
        <p class="break-words">{{ $item['sentence'] }}</p>
        <p class="mt-0.5 text-xs text-slate-500">
            <time datetime="{{ $item['time']?->toIso8601String() }}" title="{{ $item['time']?->format('d/m/Y H:i') }}">{{ $item['time']?->diffForHumans() }}</time>
            @if ($projectName)
                · @if ($projectUrl)<a href="{{ $projectUrl }}" class="hover:underline">{{ $projectName }}</a>@else{{ $projectName }}@endif
            @endif
        </p>
    </div>
</li>
