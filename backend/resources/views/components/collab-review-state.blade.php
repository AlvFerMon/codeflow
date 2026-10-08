@props(['state'])
@php($labels = \App\Http\Controllers\Web\Collab\CollabLabels::class)
<x-badge :color="$labels::REVIEW_STATE_COLORS[$state] ?? 'slate'" {{ $attributes }}>{{ $labels::REVIEW_STATES[$state] ?? $state }}</x-badge>
