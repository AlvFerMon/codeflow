<x-app-layout title="Explorar repositorios públicos">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Explorar repositorios públicos</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Repositorios que sus propietarios han hecho públicos. Puedes verlos sin iniciar sesión.</p>
    </div>

    <form method="GET" action="{{ route('explore') }}" class="mb-4 flex flex-col gap-2 sm:flex-row" role="search">
        <label class="sr-only" for="q">Buscar</label>
        <input id="q" type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Buscar por nombre o descripción…"
            class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm shadow-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500">
        <div class="flex gap-2">
            <x-button class="flex-1 sm:flex-none">Buscar</x-button>
            @if ($q !== '')
                <x-button variant="ghost" :href="route('explore')">Limpiar</x-button>
            @endif
        </div>
    </form>

    <x-card>
        @if ($repositories->isEmpty())
            <x-empty :title="$q !== '' ? 'No hay repositorios públicos que coincidan con «'.$q.'»' : 'Todavía no hay repositorios públicos'">
                @if ($q !== '')Prueba con otras palabras.@endif
            </x-empty>
        @else
            <div class="border-b border-zinc-200 dark:border-zinc-800 px-4 py-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $repositories->total() }} {{ $repositories->total() === 1 ? 'repositorio' : 'repositorios' }}{{ $q !== '' ? ' para «'.$q.'»' : '' }}
            </div>
            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($repositories as $repository)
                    <li class="px-4 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $repository->project?->name }} /</span>
                            <a href="{{ route('repositories.show', $repository) }}" class="font-mono text-sm font-semibold text-purple-700 dark:text-purple-300 hover:underline">{{ $repository->name }}</a>
                            <x-badge color="green">Público</x-badge>
                        </div>
                        @if ($repository->description)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $repository->description }}</p>
                        @endif
                        <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Actualizado {{ $repository->updated_at?->diffForHumans() }}</p>
                    </li>
                @endforeach
            </ul>
            <x-account-pagination :paginator="$repositories" />
        @endif
    </x-card>
</x-app-layout>
