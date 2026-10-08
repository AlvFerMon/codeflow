{{-- Markdown del usuario con el HTML escapado y sin enlaces inseguros. --}}
@props(['text' => null, 'empty' => 'Sin descripción.'])
@if (filled($text))
    <div {{ $attributes->merge(['class' => 'markdown break-words']) }}>{!! \Illuminate\Support\Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
@else
    <p {{ $attributes->merge(['class' => 'text-sm italic text-slate-400']) }}>{{ $empty }}</p>
@endif
