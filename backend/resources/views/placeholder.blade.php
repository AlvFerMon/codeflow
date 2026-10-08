<x-app-layout :title="$title">
    @isset($repository)
        @include('repositories._header', ['repository' => $repository, 'active' => $active ?? 'code'])
    @endisset
    <x-card>
        <x-empty :title="$title">
            Esta sección todavía no está implementada. Se completará en los siguientes pasos.
        </x-empty>
    </x-card>
</x-app-layout>
