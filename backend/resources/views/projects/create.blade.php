<x-app-layout title="Nuevo proyecto">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('projects.index') }}" class="text-sm text-slate-500 hover:underline">← Mis proyectos</a>
        <h1 class="mb-1 mt-2 text-2xl font-semibold text-slate-900">Nuevo proyecto</h1>
        <p class="mb-6 text-sm text-slate-500">Serás su propietario: podrás crear repositorios y dar acceso a otros usuarios.</p>
        <x-card>
            <form method="POST" action="{{ route('projects.store') }}" class="space-y-4 p-4 sm:p-6">
                @csrf
                @include('projects._form')
                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" :href="route('projects.index')">Cancelar</x-button>
                    <x-button>Crear proyecto</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
