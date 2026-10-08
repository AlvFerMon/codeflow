@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])
@php
    $classes = 'inline-flex items-center justify-center gap-1.5 rounded-md px-3.5 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50 '.match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-300',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-500 focus:ring-rose-400',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-500 focus:ring-emerald-400',
        'ghost' => 'text-slate-600 hover:bg-slate-100 focus:ring-slate-300',
        default => 'bg-indigo-600 text-white hover:bg-indigo-500 focus:ring-indigo-400',
    };
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
