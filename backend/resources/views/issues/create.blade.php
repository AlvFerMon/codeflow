@php($L = \App\Http\Controllers\Web\Collab\CollabLabels::class)
<x-app-layout :title="'Nueva issue · '.$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'issues'])

    <form method="POST" action="{{ route('issues.store', $repository) }}" class="grid gap-6 lg:grid-cols-[1fr_18rem]">
        @csrf
        <x-card title="Nueva issue">
            <div class="space-y-4 p-4">
                <x-input label="Título" name="title" required maxlength="200" autofocus placeholder="Resumen breve del error o la tarea" />
                <div>
                    <x-textarea label="Descripción" name="description" rows="10" placeholder="Pasos para reproducirlo, comportamiento esperado, capturas…" />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Admite Markdown: **negrita**, `código`, listas, enlaces y bloques ```código```.</p>
                </div>
            </div>
        </x-card>

        <div class="space-y-4">
            <x-card>
                <div class="space-y-4 p-4">
                    <x-select label="Prioridad" name="priority" :options="$L::PRIORITIES" value="medium" />
                    <x-select label="Asignar a" name="assignee_id" :options="$members" placeholder="Sin asignar" />
                    <x-input label="Etiquetas" name="labels" placeholder="bug, backend" help="Separadas por comas (máx. 20)." />
                </div>
            </x-card>
            <div class="flex gap-2">
                <x-button class="flex-1">Crear issue</x-button>
                <x-button variant="secondary" :href="route('issues.index', $repository)">Cancelar</x-button>
            </div>
        </div>
    </form>
</x-app-layout>
