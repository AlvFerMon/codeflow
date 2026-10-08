@props(['priority'])
@php($labels = \App\Http\Controllers\Web\Collab\CollabLabels::class)
<x-badge :color="$labels::PRIORITY_COLORS[$priority] ?? 'slate'" {{ $attributes }}>{{ $labels::PRIORITIES[$priority] ?? $priority }}</x-badge>
