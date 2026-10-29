@php
    $kinds = [
        'modified_both' => 'Las dos ramas cambiaron las mismas líneas',
        'added_both' => 'Las dos ramas crearon este archivo con contenido distinto',
        'deleted_ours' => 'Se borró en '.$pr->source_branch_name.' y se cambió en '.$pr->target_branch_name,
        'deleted_theirs' => 'Se cambió en '.$pr->source_branch_name.' y se borró en '.$pr->target_branch_name,
    ];
@endphp
<x-app-layout :title="'Resolver conflictos · #'.$pr->number">
    @include('repositories._header', ['repository' => $repository, 'active' => 'pulls'])

    <div class="mb-6">
        <p class="text-sm"><a href="{{ route('pulls.show', [$repository, $pr->number]) }}" class="text-zinc-500 hover:underline dark:text-zinc-400">#{{ $pr->number }} {{ $pr->title }}</a> /</p>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">Resolver conflictos</h1>
        <p class="mt-1 max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
            {{ count($files) === 1 ? 'Este archivo tiene' : 'Estos '.count($files).' archivos tienen' }} cambios en las mismas líneas en
            <span class="font-mono">{{ $pr->source_branch_name }}</span> y en <span class="font-mono">{{ $pr->target_branch_name }}</span>.
            Decide cómo debe quedar cada uno. Se guardará como un commit en <span class="font-mono">{{ $pr->source_branch_name }}</span> y después podrás fusionar.
        </p>
        <details class="mt-3 max-w-3xl rounded-lg border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <summary class="cursor-pointer font-semibold">¿Cómo se lee un conflicto?</summary>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Cada conflicto va entre marcas. Arriba, lo de <span class="font-mono">{{ $pr->source_branch_name }}</span>; abajo, lo de <span class="font-mono">{{ $pr->target_branch_name }}</span>. Deja el texto como debe quedar y borra las tres líneas de marca:</p>
            <pre class="mt-2 overflow-x-auto rounded bg-zinc-100 p-2 font-mono text-xs dark:bg-zinc-800">&lt;&lt;&lt;&lt;&lt;&lt;&lt; {{ $pr->source_branch_name }} (origen)
lo que hay en la rama origen
=======
lo que hay en la rama destino
&gt;&gt;&gt;&gt;&gt;&gt;&gt; {{ $pr->target_branch_name }} (destino)</pre>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">También puedes quedarte con una versión entera con los botones de cada archivo.</p>
        </details>
    </div>

    <form method="POST" action="{{ route('pulls.resolve', [$repository, $pr->number]) }}" class="space-y-6" data-conflicts>
        @csrf
        <input type="hidden" name="source_head" value="{{ $sourceHead }}">
        <input type="hidden" name="target_head" value="{{ $targetHead }}">

        @foreach ($files as $i => $file)
            @php $eol = str_contains((string) ($file['ours'] ?? $file['theirs'] ?? ''), "\r\n") ? 'crlf' : 'lf'; @endphp
            <x-card class="overflow-hidden" data-conflict-file>
                <div class="flex flex-col gap-2 border-b border-zinc-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                    <div class="min-w-0">
                        <p class="truncate font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $file['path'] }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $kinds[$file['kind']] ?? '' }}{{ $file['binary'] ? ' · archivo binario' : '' }}</p>
                    </div>
                    <span class="text-xs font-semibold" data-conflict-state aria-live="polite"></span>
                </div>
                <input type="hidden" name="files[{{ $i }}][path]" value="{{ $file['path'] }}">
                <input type="hidden" name="files[{{ $i }}][eol]" value="{{ $eol }}">

                <div class="space-y-3 p-4">
                    <fieldset class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                        <legend class="sr-only">Qué hacer con {{ $file['path'] }}</legend>
                        @if ($file['marked'] !== null)
                            <label class="inline-flex items-center gap-2"><input type="radio" name="files[{{ $i }}][action]" value="edit" checked data-choice="edit"> Editar a mano</label>
                        @endif
                        @if ($file['kind'] !== 'deleted_ours')
                            <label class="inline-flex items-center gap-2"><input type="radio" name="files[{{ $i }}][action]" value="ours" data-choice="ours" @checked($file['marked'] === null)> Quedarme con la de <span class="font-mono">{{ $pr->source_branch_name }}</span></label>
                        @endif
                        @if ($file['kind'] !== 'deleted_theirs')
                            <label class="inline-flex items-center gap-2"><input type="radio" name="files[{{ $i }}][action]" value="theirs" data-choice="theirs" @checked($file['marked'] === null && $file['kind'] === 'deleted_ours')> Quedarme con la de <span class="font-mono">{{ $pr->target_branch_name }}</span></label>
                        @endif
                        @if (in_array($file['kind'], ['deleted_ours', 'deleted_theirs'], true))
                            <label class="inline-flex items-center gap-2"><input type="radio" name="files[{{ $i }}][action]" value="delete" data-choice="delete"> Borrar el archivo</label>
                        @endif
                    </fieldset>

                    {{-- Se envía action (edit | ours | theirs | delete) y, si es edit, el contenido --}}
                    @if ($file['binary'])
                        <input type="hidden" name="files[{{ $i }}][content]" value="" data-binary>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Es un archivo binario: elige qué versión conservar.</p>
                    @endif
                    <label class="block" @if ($file['binary']) hidden @endif>
                        <span class="sr-only">Contenido final de {{ $file['path'] }}</span>
                        <textarea name="files[{{ $i }}][content]" rows="{{ min(28, max(8, substr_count((string) ($file['marked'] ?? $file['ours'] ?? $file['theirs'] ?? ''), "\n") + 2)) }}" spellcheck="false" data-content
                            class="block w-full rounded-md border border-zinc-300 bg-white p-3 font-mono text-xs leading-5 dark:border-zinc-700 dark:bg-zinc-950" @disabled($file['binary'])>{{ $file['marked'] ?? $file['ours'] ?? $file['theirs'] ?? '' }}</textarea>
                    </label>
                    <template data-version="ours">{{ $file['ours'] ?? '' }}</template>
                    <template data-version="theirs">{{ $file['theirs'] ?? '' }}</template>
                    <template data-version="edit">{{ $file['marked'] ?? '' }}</template>
                </div>
            </x-card>
        @endforeach

        <div class="flex flex-wrap items-center gap-3">
            <x-button variant="success">Guardar la resolución</x-button>
            <x-button variant="ghost" :href="route('pulls.show', [$repository, $pr->number])">Cancelar</x-button>
            <p class="text-sm text-zinc-500 dark:text-zinc-400" data-conflicts-summary aria-live="polite"></p>
        </div>
    </form>
</x-app-layout>
