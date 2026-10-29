<x-app-layout title="Papelera">
    <div class="mb-6 border-b border-zinc-200/80 pb-6 dark:border-zinc-800">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">Papelera</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Los proyectos y repositorios que borras se guardan aquí {{ $days }} días. Después se eliminan definitivamente.</p>
    </div>

    @if ($projects->isEmpty() && $repositories->isEmpty())
        <x-card><x-empty title="La papelera está vacía">Cuando borres un proyecto o un repositorio aparecerá aquí y podrás recuperarlo.</x-empty></x-card>
    @else
        <div class="space-y-6">
            @foreach ([
                ['Proyectos', $projects, 'trash.projects', 'el proyecto'],
                ['Repositorios', $repositories, 'trash.repositories', 'el repositorio'],
            ] as [$heading, $items, $route, $what])
                @if ($items->isNotEmpty())
                    <x-card :title="$heading">
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($items as $item)
                                <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $item['name'] }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            @isset($item['project'])en {{ $item['project']['name'] }} · @endisset
                                            @isset($item['repositories']){{ $item['repositories'] }} {{ $item['repositories'] === 1 ? 'repositorio' : 'repositorios' }} · @endisset
                                            borrado {{ $item['deleted_at']->diffForHumans() }} · se eliminará {{ $item['expires_at']->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route($route.'.restore', $item['id']) }}">
                                            @csrf
                                            <x-button variant="secondary" size="sm">Restaurar</x-button>
                                        </form>
                                        <form method="POST" action="{{ route($route.'.destroy', $item['id']) }}"
                                            data-confirm-title="Borrar definitivamente" data-confirm-button="Borrar para siempre"
                                            data-confirm="Se borrará {{ $what }} «{{ $item['name'] }}» con todo su contenido. Esto no se puede deshacer.">
                                            @csrf
                                            @method('DELETE')
                                            <x-button variant="ghost" size="sm" class="text-rose-600! dark:text-rose-400!">Borrar para siempre</x-button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif
            @endforeach
        </div>
    @endif
</x-app-layout>
