{{-- Lista «Primeros pasos» del tutorial. Las tareas se marcan solas (OnboardingService).
     Se puede ocultar unos días; vuelve mientras no esté completa. --}}
@php
    $state = app(\App\Services\OnboardingService::class)->state(auth()->user());
    $list = $state['checklist'];
    $sample = $state['sample_project'];
    $percent = $list['required_total'] ? (int) round($list['required_done'] * 100 / $list['required_total']) : 100;
@endphp
@if ($list['visible'])
    <section data-tour="primeros-pasos" aria-labelledby="primeros-pasos" class="ficha mb-8 rounded-lg p-5">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <h2 id="primeros-pasos" class="text-sm font-extrabold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Primeros pasos</h2>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Se marcan solos según los vas haciendo. {{ $list['required_done'] }} de {{ $list['required_total'] }} hechos.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($sample['url'])
                    <x-button variant="secondary" size="sm" :href="$sample['url']">Ir al proyecto de práctica</x-button>
                @else
                    <form method="POST" action="{{ route('onboarding.sample') }}">
                        @csrf
                        <x-button variant="amethyst" size="sm" title="Crea un proyecto con ejemplos para practicar sin miedo">Crear proyecto de práctica</x-button>
                    </form>
                @endif
                <form method="POST" action="{{ route('onboarding.checklist.hide') }}">
                    @csrf
                    <x-button variant="ghost" size="sm" title="Se ocultará {{ config('onboarding.checklist_snooze_days') }} días">Ocultar</x-button>
                </form>
            </div>
        </div>

        <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800" role="progressbar" aria-label="Progreso de primeros pasos" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}">
            <div class="h-full rounded-full bg-[#2EC4B6]" style="width: {{ $percent }}%"></div>
        </div>

        <ol class="mt-4 grid gap-2 sm:grid-cols-2">
            @foreach ($list['items'] as $item)
                <li>
                    <a href="{{ $item['url'] }}" @class([
                        'flex items-start gap-3 rounded-lg border p-3 transition-colors',
                        'border-zinc-200 hover:border-[#FF3366]/50 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60' => ! $item['done'],
                        'border-transparent bg-zinc-50 dark:bg-zinc-800/40' => $item['done'],
                    ])>
                        <span @class([
                            'mt-0.5 grid h-5 w-5 flex-none place-items-center rounded-full text-[11px] font-bold',
                            'bg-[#2EC4B6] text-white' => $item['done'],
                            'border-2 border-zinc-300 dark:border-zinc-600' => ! $item['done'],
                        ]) aria-hidden="true">@if ($item['done'])✓@endif</span>
                        <span>
                            <span @class(['block text-sm font-semibold', 'text-zinc-500 line-through dark:text-zinc-500' => $item['done'], 'text-zinc-900 dark:text-zinc-100' => ! $item['done']])>
                                {{ $item['title'] }}<span class="sr-only">{{ $item['done'] ? ' (hecho)' : ' (pendiente)' }}</span>
                            </span>
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $item['text'] }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    </section>
@endif
