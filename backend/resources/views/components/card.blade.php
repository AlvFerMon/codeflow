@props(['title' => null])
<section {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">{{ $title }}</h2>
            {{ $actions ?? '' }}
        </header>
    @endif
    {{ $slot }}
</section>
