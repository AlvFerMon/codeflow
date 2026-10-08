@props(['role' => null])
@php
    $color = match ($role) {
        \App\Support\Roles::OWNER => 'purple',
        \App\Support\Roles::COLLABORATOR => 'blue',
        \App\Support\Roles::OBSERVER => 'amber',
        default => 'slate',
    };
@endphp
<x-badge :color="$color" {{ $attributes }}>{{ ucfirst(\App\Support\Roles::label($role)) }}</x-badge>
