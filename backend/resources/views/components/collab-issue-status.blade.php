@props(['status'])
@php($labels = \App\Http\Controllers\Web\Collab\CollabLabels::class)
<x-badge :color="$labels::ISSUE_STATUS_COLORS[$status] ?? 'slate'" {{ $attributes }}>
    <x-collab-status-icon type="issue" :status="$status" class="h-3.5 w-3.5" />{{ $labels::ISSUE_STATUSES[$status] ?? $status }}
</x-badge>
