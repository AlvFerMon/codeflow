<x-app-layout title="Editar proyecto">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('projects.show', $project) }}" class="text-sm text-slate-500 hover:underline">← {{ $project->name }}</a>
        <h1 class="mb-6 mt-2 text-2xl font-semibold text-slate-900">Editar proyecto</h1>
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
            <x-card class="mt-6 border-rose-200">
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div>
                        <h2 class="font-semibold text-rose-700">Eliminar el proyecto</h2>
                        <p class="text-sm text-slate-600">Se borrarán sus repositorios, issues, pull requests y actividad. No se puede deshacer.</p>
                    </div>
                    <form method="POST" action="{{ route('projects.destroy', $project) }}"
                        onsubmit="return confirm(@js('¿Seguro que quieres eliminar el proyecto «'.$project->name.'» y todo su contenido? Esta acción no se puede deshacer.'))">
                        @csrf
                        @method('DELETE')
                        <x-button variant="danger" class="w-full sm:w-auto">Eliminar proyecto</x-button>
                    </form>
                </div>
            </x-card>
        @endcan
    </div>
</x-app-layout>
