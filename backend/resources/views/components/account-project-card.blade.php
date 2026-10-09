{{-- Tarjeta de proyecto. $project con repositories_count y, opcionalmente, my_role / owner. --}}
@props(['project'])
<div {{ $attributes->merge(['class' => 'group relative flex flex-col rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm transition hover:border-purple-300 dark:hover:border-purple-500/30 hover:shadow']) }}>
    <div class="flex items-start justify-between gap-2">
        <h3 class="min-w-0 break-words font-semibold text-zinc-900 dark:text-zinc-100 group-hover:text-purple-700 dark:group-hover:text-purple-300">
            {{-- El enlace cubre toda la tarjeta; los enlaces internos quedan por encima (z-10) --}}
            <a href="{{ route('projects.show', $project) }}" class="after:absolute after:inset-0 after:rounded-xl">{{ $project->name }}</a>
        </h3>
        @if ($project->my_role)
            <x-account-role-badge :role="$project->my_role" class="shrink-0" />
        @else
            <x-badge color="slate" class="shrink-0">Sin rol</x-badge>
        @endif
    </div>
    <p class="mt-1 line-clamp-2 flex-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $project->description ? \Illuminate\Support\Str::limit(strip_tags(\Illuminate\Support\Str::markdown($project->description, ['html_input' => 'strip'])), 160) : 'Sin descripción.' }}</p>
    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
        <span class="inline-flex items-center gap-1">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
            {{ $project->repositories_count }} {{ $project->repositories_count === 1 ? 'repositorio' : 'repositorios' }}
        </span>
        @isset($project->open_issues_count)
            <a href="{{ route('projects.issues', $project) }}" class="relative z-10 hover:text-purple-600 dark:hover:text-purple-400">{{ $project->open_issues_count }} {{ $project->open_issues_count === 1 ? 'issue abierta' : 'issues abiertas' }}</a>
            <a href="{{ route('projects.pulls', $project) }}" class="relative z-10 hover:text-purple-600 dark:hover:text-purple-400">{{ $project->open_pulls_count }} {{ $project->open_pulls_count === 1 ? 'PR pendiente' : 'PRs pendientes' }}</a>
        @endisset
        @if ($project->relationLoaded('owner') && $project->owner)
            <span>Propietario: {{ $project->owner->name }}</span>
        @endif
        <span>Actualizado {{ $project->updated_at?->diffForHumans() }}</span>
    </div>
</div>
