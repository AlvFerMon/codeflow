{{-- Migas de pan de una ruta del repositorio. Las carpetas enlazan al explorador; el último tramo es texto. --}}
@props(['repository', 'branch' => null, 'path' => '', 'linkLast' => false])
@php
    $segments = $path === '' ? [] : explode('/', $path);
    $branchName = $branch?->name;
    $params = fn (string $p) => array_filter([$repository, 'branch' => $branchName !== $repository->default_branch ? $branchName : null, 'path' => $p]);
@endphp
<nav {{ $attributes->merge(['class' => 'flex min-w-0 flex-wrap items-center gap-x-1 font-mono text-sm']) }} aria-label="Ruta">
    <a href="{{ route('repositories.show', $params('')) }}" class="font-semibold text-indigo-600 hover:underline">{{ $repository->name }}</a>
    @foreach ($segments as $i => $segment)
        <span class="text-slate-400">/</span>
        @php($partial = implode('/', array_slice($segments, 0, $i + 1)))
        @if ($i < count($segments) - 1 || $linkLast)
            <a href="{{ route('repositories.show', $params($partial)) }}" class="break-all text-indigo-600 hover:underline">{{ $segment }}</a>
        @else
            <span class="break-all font-semibold text-slate-900">{{ $segment }}</span>
        @endif
    @endforeach
</nav>
