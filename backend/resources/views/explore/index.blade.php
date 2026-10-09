<x-app-layout title="Explorar repositorios públicos">
    <div class="mb-6 flex flex-col justify-between gap-3 border-b border-zinc-200/80 pb-6 md:flex-row md:items-end dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">Explorar repositorios públicos</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Código que sus autores han compartido. Puedes leerlo, ver su historial, sus issues y sus pull requests sin crear una cuenta.</p>
        </div>
        @guest
            <div class="flex gap-2">
                <x-button variant="secondary" size="sm" :href="route('login')">Iniciar sesión</x-button>
                <x-button variant="purple" size="sm" :href="route('register')">Crear cuenta</x-button>
            </div>
        @endguest
    </div>

    <form method="GET" action="{{ route('explore') }}" class="mb-4 flex flex-col gap-2 sm:flex-row" role="search">
        <label class="sr-only" for="q">Buscar repositorios</label>
        <input id="q" type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Buscar por nombre, descripción o proyecto…"
            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 dark:border-zinc-700 dark:bg-zinc-900">
        <label class="sr-only" for="orden">Ordenar por</label>
        <select id="orden" name="orden" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 sm:w-52 dark:border-zinc-700 dark:bg-zinc-900" onchange="this.form.requestSubmit()">
            @foreach ($sorts as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <x-button class="flex-1 sm:flex-none">Buscar</x-button>
            @if ($q !== '')
                <x-button variant="ghost" :href="route('explore', ['orden' => $sort])">Limpiar</x-button>
            @endif
        </div>
    </form>

    <x-card>
        @if ($repositories->isEmpty())
            <x-empty :title="$q !== '' ? 'No hay repositorios públicos que coincidan con «'.$q.'»' : 'Todavía no hay repositorios públicos'">
                @if ($q !== '')Prueba con otras palabras o con el nombre del proyecto.@else Cuando alguien haga público un repositorio aparecerá aquí.@endif
            </x-empty>
        @else
            <div class="border-b border-zinc-100 px-5 py-2.5 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                {{ $repositories->total() }} {{ $repositories->total() === 1 ? 'repositorio' : 'repositorios' }}{{ $q !== '' ? ' para «'.$q.'»' : '' }} · {{ $sorts[$sort] }}
            </div>
            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($repositories as $repository)
                    <x-public-repo-row :repository="$repository" counts />
                @endforeach
            </ul>
            <x-pagination :paginator="$repositories" />
        @endif
    </x-card>
</x-app-layout>
