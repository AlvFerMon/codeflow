{{-- Estado vacío: título, explicación (slot) y, opcionalmente, una acción para el siguiente paso. --}}
@props(['title' => 'No hay nada todavía', 'action' => null, 'href' => null, 'icon' => true])
<div {{ $attributes->merge(['class' => 'px-6 py-12 text-center']) }}>
    @if ($icon)
        <span class="mx-auto mb-3 grid h-10 w-10 place-items-center rounded-md border-[1.5px] border-dashed border-zinc-300 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400" aria-hidden="true">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
        </span>
    @endif
    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $title }}</p>
    @if (trim($slot) !== '')
        <div class="mx-auto mt-1 max-w-md text-sm text-zinc-500 dark:text-zinc-400">{{ $slot }}</div>
    @endif
    @if ($action && $href)
        <div class="mt-4"><x-button variant="purple" size="sm" :href="$href">{{ $action }}</x-button></div>
    @endif
</div>
