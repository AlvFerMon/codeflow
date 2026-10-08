<x-app-layout title="Explorar repositorios públicos">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Explorar repositorios públicos</h1>
        <p class="text-sm text-slate-500">Repositorios que sus propietarios han hecho públicos. Puedes verlos sin iniciar sesión.</p>
    </div>

    <form method="GET" action="{{ route('explore') }}" class="mb-4 flex flex-col gap-2 sm:flex-row" role="search">
        <label class="sr-only" for="q">Buscar</label>
        <input id="q" type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Buscar por nombre o descripción…"
            class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
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
            <div class="border-b border-slate-200 px-4 py-2 text-xs text-slate-500">
                {{ $repositories->total() }} {{ $repositories->total() === 1 ? 'repositorio' : 'repositorios' }}{{ $q !== '' ? ' para «'.$q.'»' : '' }}
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($repositories as $repository)
                    <li class="px-4 py-4 hover:bg-slate-50">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="text-sm text-slate-500">{{ $repository->project?->name }} /</span>
                            <a href="{{ route('repositories.show', $repository) }}" class="font-mono text-sm font-semibold text-indigo-700 hover:underline">{{ $repository->name }}</a>
                            <x-badge color="green">Público</x-badge>
                        </div>
                        @if ($repository->description)
                            <p class="mt-1 text-sm text-slate-600">{{ $repository->description }}</p>
                        @endif
                        <p class="mt-1 text-xs text-slate-400">Actualizado {{ $repository->updated_at?->diffForHumans() }}</p>
                    </li>
                @endforeach
            </ul>
            <x-account-pagination :paginator="$repositories" />
        @endif
    </x-card>
</x-app-layout>
