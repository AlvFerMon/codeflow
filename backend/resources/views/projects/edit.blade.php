<x-app-layout :title="'Configuración · '.$project->name">
    @include('projects._header', ['project' => $project, 'active' => 'settings'])
    <div class="max-w-2xl">
        <x-card>
            <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-4 p-4 sm:p-6">
                @csrf
                @method('PUT')
                @include('projects._form', ['project' => $project])
                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" :href="route('projects.show', $project)">Cancelar</x-button>
                    <x-button>Guardar cambios</x-button>
                </div>
            </form>
        </x-card>

        @can('delete', $project)
            <x-card class="mt-6 border-rose-200 dark:border-rose-500/30">
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div>
                        <h2 class="font-semibold text-rose-700 dark:text-rose-300">Eliminar el proyecto</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Se borrarán sus repositorios, issues, pull requests y actividad. No se puede deshacer.</p>
                    </div>
                    <form method="POST" action="{{ route('projects.destroy', $project) }}"
                        data-confirm-title="Eliminar el proyecto" data-confirm-button="Eliminar proyecto" data-confirm="Se borrarán el proyecto «{{ $project->name }}», todos sus repositorios, su historial, issues y pull requests. Esta acción no se puede deshacer.">
                        @csrf
                        @method('DELETE')
                        <x-button variant="danger" class="w-full sm:w-auto">Eliminar proyecto</x-button>
                    </form>
                </div>
            </x-card>
        @endcan
    </div>
</x-app-layout>
