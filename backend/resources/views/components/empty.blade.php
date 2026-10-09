@props(['title' => 'No hay nada todavía'])
<div {{ $attributes->merge(['class' => 'px-6 py-12 text-center']) }}>
    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $title }}</p>
    @if (trim($slot) !== '')
        <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $slot }}</div>
    @endif
</div>
