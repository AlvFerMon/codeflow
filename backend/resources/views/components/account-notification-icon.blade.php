{{-- Icono de una notificación según su tipo de evento y si es alerta. --}}
@props(['event' => null, 'alert' => false])
@php
    $group = $alert ? 'alert' : strtok((string) $event, '.');
    [$classes, $path] = match ($group) {
        'alert' => ['bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
        'member' => ['bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300', 'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z'],
        'issue' => ['bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300', 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z'],
        'pull_request' => ['bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300', 'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
        'commit', 'branch' => ['bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300', 'M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5'],
        default => ['bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400', 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'grid h-8 w-8 shrink-0 place-items-center rounded-full '.$classes]) }}>
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
</span>
