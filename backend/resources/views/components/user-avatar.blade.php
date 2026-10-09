{{-- Avatar con iniciales y degradado del diseño. --}}
@props(['user', 'size' => 'h-7 w-7 text-xs'])
@php
    $parts = preg_split('/\s+/', trim($user?->name ?? '?'));
    $initials = mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1).(isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
@endphp
<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-purple-500 to-indigo-600 font-semibold text-white ring-2 ring-white dark:ring-zinc-800 {$size}"]) }} aria-hidden="true">{{ $initials }}</span>
