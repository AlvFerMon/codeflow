{{-- Avatar con la inicial del usuario y un color estable por usuario. Props: user (User|null), size (xs|sm|md|lg) --}}
@props(['user' => null, 'size' => 'md'])
@php
    $palette = ['bg-purple-500', 'bg-sky-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500', 'bg-indigo-500', 'bg-teal-500', 'bg-fuchsia-500'];
    $bg = $user ? $palette[$user->id % count($palette)] : 'bg-zinc-300 dark:bg-zinc-600';
    $dim = match ($size) {
        'xs' => 'h-5 w-5 text-[10px]',
        'sm' => 'h-6 w-6 text-[11px]',
        'lg' => 'h-10 w-10 text-sm',
        default => 'h-7 w-7 text-xs',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-grid shrink-0 place-items-center rounded-full font-semibold text-white {$dim} {$bg}"]) }}
    title="{{ $user?->name ?? 'Usuario eliminado' }}" aria-hidden="true">{{ $user ? mb_strtoupper(mb_substr($user->name, 0, 1)) : '?' }}</span>
