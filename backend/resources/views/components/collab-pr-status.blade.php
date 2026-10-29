@props(['status'])
@php($labels = \App\Http\Controllers\Web\Collab\CollabLabels::class)
<x-badge :color="$labels::PR_STATUS_COLORS[$status] ?? 'slate'" :shape="in_array($status, ['merged', 'approved'], true) ? 'done' : (in_array($status, ['closed'], true) ? 'closed' : 'pending')" {{ $attributes }}>
    <x-collab-status-icon type="pull" :status="$status" class="h-3.5 w-3.5" />{{ $labels::PR_STATUSES[$status] ?? $status }}
</x-badge>
