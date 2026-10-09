{{-- Selector de tema: claro / oscuro / sistema (lo gestiona resources/js/theme.js). --}}
<div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5 dark:border-zinc-700 dark:bg-zinc-800" role="group" aria-label="Tema de color">
    @foreach (['light' => 'Claro', 'dark' => 'Oscuro', 'system' => 'Sistema'] as $value => $label)
        <button type="button" data-theme-set="{{ $value }}" aria-pressed="false"
            class="rounded-lg px-2 py-1 text-[11px] font-medium text-zinc-500 transition-colors hover:text-zinc-900 aria-pressed:bg-white aria-pressed:text-zinc-900 aria-pressed:shadow-xs dark:text-zinc-400 dark:hover:text-zinc-100 dark:aria-pressed:bg-zinc-700 dark:aria-pressed:text-white">{{ $label }}</button>
    @endforeach
</div>
