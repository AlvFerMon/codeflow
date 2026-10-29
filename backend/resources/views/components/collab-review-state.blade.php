@props(['state'])
@php($labels = \App\Http\Controllers\Web\Collab\CollabLabels::class)
<x-badge :color="$labels::REVIEW_STATE_COLORS[$state] ?? 'slate'" :shape="in_array($state, ['approved'], true) ? 'done' : (in_array($state, [], true) ? 'closed' : 'pending')" {{ $attributes }}>{{ $labels::REVIEW_STATES[$state] ?? $state }}</x-badge>
