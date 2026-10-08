{{-- Inicial del usuario en un círculo. user: App\Models\User|null --}}
@props(['user' => null, 'size' => 'h-6 w-6 text-[11px]'])
<span {{ $attributes->merge(['class' => 'grid shrink-0 place-items-center rounded-full bg-indigo-100 font-semibold text-indigo-700 '.$size]) }} title="{{ $user?->name ?? 'Usuario eliminado' }}">{{ $user ? mb_strtoupper(mb_substr($user->name, 0, 1)) : '?' }}</span>
