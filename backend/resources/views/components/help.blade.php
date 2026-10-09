{{-- Ayuda breve para usuarios no técnicos: «¿Qué es…?» que se despliega con clic o teclado. --}}
@props(['summary'])
<details {{ $attributes->merge(['class' => 'group text-xs']) }}>
    <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded font-medium text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/></svg>
        {{ $summary }}
        <svg class="h-3 w-3 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
    </summary>
    <div class="mt-2 max-w-3xl rounded-lg border border-purple-500/20 bg-purple-500/5 px-3 py-2 leading-5 text-zinc-700 dark:text-zinc-300">{{ $slot }}</div>
</details>
