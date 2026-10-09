{{-- Inicial del usuario en un círculo de color estable. Props: user (User|null), size (sm|md) --}}
@props(['user' => null, 'size' => 'md'])
@php
    $palette = ['bg-purple-500', 'bg-sky-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500', 'bg-indigo-500', 'bg-teal-500', 'bg-zinc-500'];
    $bg = $user ? $palette[$user->id % count($palette)] : 'bg-zinc-300 dark:bg-zinc-600';
    $dim = $size === 'sm' ? 'h-6 w-6 text-[11px]' : 'h-8 w-8 text-xs';
@endphp
<span {{ $attributes->merge(['class' => "inline-grid shrink-0 place-items-center rounded-full font-semibold text-white {$dim} {$bg}"]) }}
    title="{{ $user?->name ?? 'Usuario eliminado' }}">{{ $user ? mb_strtoupper(mb_substr($user->name, 0, 1)) : '?' }}</span>
