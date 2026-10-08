@props(['title' => 'No hay nada todavía'])
<div {{ $attributes->merge(['class' => 'px-6 py-12 text-center']) }}>
    <p class="text-sm font-medium text-slate-700">{{ $title }}</p>
    @if (trim($slot) !== '')
        <div class="mt-1 text-sm text-slate-500">{{ $slot }}</div>
    @endif
</div>
