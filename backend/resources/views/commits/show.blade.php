<x-app-layout :title="'Commit '.substr($commit->sha, 0, 7).' · '.$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'commits'])

    @php
        $message = (string) $commit->message;
        $summary = \Illuminate\Support\Str::before($message, "\n");
        $body = trim(\Illuminate\Support\Str::after($message, "\n"));
        $body = $body === $summary ? '' : $body;
    @endphp

    <x-card class="mb-6 overflow-hidden">
        <div class="p-4 sm:p-5">
            <div class="flex flex-wrap items-start gap-3">
                <h1 class="min-w-0 flex-1 break-words text-lg font-semibold text-slate-900">{{ $summary }}</h1>
                @if ($commit->second_parent_id)
                    <x-badge color="purple">commit de fusión</x-badge>
                @endif
            </div>
            @if ($body !== '')
                <div class="mt-3 whitespace-pre-wrap break-words text-sm text-slate-700">{{ $body }}</div>
            @endif
            @if ($branchesAtCommit->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-1">
                    @foreach ($branchesAtCommit as $name)
                        <a href="{{ route('repositories.show', array_filter([$repository, 'branch' => $name !== $repository->default_branch ? $name : null])) }}" class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-0.5 font-mono text-xs text-indigo-700 hover:underline">
                            <x-code-icon name="branch" class="h-3.5 w-3.5" /> {{ $name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6 sm:px-5">
            <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <x-code-avatar :user="$commit->author" />
                <span class="font-medium text-slate-800">{{ $commit->author?->name ?? 'Usuario eliminado' }}</span>
                <span class="whitespace-nowrap text-slate-500">· <time datetime="{{ $commit->created_at->toIso8601String() }}" title="{{ $commit->created_at->diffForHumans() }}">{{ $commit->created_at->translatedFormat('j \d\e F \d\e Y, H:i') }}</time></span>
            </span>
            <span class="flex flex-wrap items-center gap-2 text-slate-600 sm:ml-auto">
                @if ($parents->isEmpty())
                    <span class="text-xs text-slate-500">Commit inicial (sin padres)</span>
                @else
                    <span class="text-xs">{{ $parents->count() === 1 ? 'Padre' : 'Padres' }}:</span>
                    @foreach ($parents as $parent)
                        <a href="{{ route('commits.show', [$repository, $parent->sha]) }}" class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-mono text-xs text-indigo-600 hover:underline">{{ substr($parent->sha, 0, 7) }}</a>
                    @endforeach
                @endif
                <span class="text-xs">Commit:</span>
                <code class="break-all rounded bg-white px-1.5 py-0.5 font-mono text-xs text-slate-700 ring-1 ring-slate-200" title="SHA-256 del contenido del commit">{{ $commit->sha }}</code>
            </span>
        </div>
    </x-card>

    @if ($commit->second_parent_id)
        <p class="mb-3 text-xs text-slate-500">Los cambios se muestran respecto al primer padre ({{ substr($parents->first()?->sha ?? '', 0, 7) }}).</p>
    @endif

    @if (empty($files))
        <x-card><x-empty title="Este commit no cambia ningún archivo" /></x-card>
    @else
        <x-diff :files="$files" />
    @endif
</x-app-layout>
