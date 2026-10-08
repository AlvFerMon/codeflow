@props(['color' => 'slate'])
@php
    $map = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'red' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-200',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.($map[$color] ?? $map['slate'])]) }}>{{ $slot }}</span>
