@props(['label'])
<x-badge :color="\App\Http\Controllers\Web\Collab\CollabLabels::labelColor($label)" {{ $attributes }}>{{ $label }}</x-badge>
