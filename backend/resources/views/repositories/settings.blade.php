<x-app-layout :title="'Configuración · '.$repository->name">
    @include('repositories._header', ['repository' => $repository, 'active' => 'settings'])

    <div class="mx-auto max-w-3xl space-y-6">
        <x-card title="General">
            <form method="POST" action="{{ route('repositories.update', $repository) }}" class="space-y-5 p-4 sm:p-6">
                @csrf
                @method('PUT')
                <x-input name="name" label="Nombre" :value="$repository->name" required maxlength="100" pattern="[A-Za-z0-9._\-]+"
                    help="Letras, números, puntos, guiones y guiones bajos." />
                <x-textarea name="description" label="Descripción" :value="$repository->description" rows="3" maxlength="2000" />
                <x-select name="default_branch" label="Rama por defecto" :options="$branches" :value="$repository->default_branch" class="font-mono" />

                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">Visibilidad</legend>
                    @php($visibility = old('visibility', $repository->visibility))
                    <div class="space-y-2">
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 has-[:checked]:border-purple-400 has-[:checked]:bg-purple-50/50 dark:has-[:checked]:border-purple-500/60 dark:has-[:checked]:bg-purple-500/10">
                            <input type="radio" name="visibility" value="private" class="mt-1" @checked($visibility === 'private')>
                            <span>
                                <span class="flex items-center gap-1.5 text-sm font-medium text-zinc-800 dark:text-zinc-200"><x-code-icon name="lock" /> Privado</span>
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">Solo los miembros del proyecto pueden verlo.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 has-[:checked]:border-purple-400 has-[:checked]:bg-purple-50/50 dark:has-[:checked]:border-purple-500/60 dark:has-[:checked]:bg-purple-500/10">
                            <input type="radio" name="visibility" value="public" class="mt-1" @checked($visibility === 'public')>
                            <span>
                                <span class="flex items-center gap-1.5 text-sm font-medium text-zinc-800 dark:text-zinc-200"><x-code-icon name="globe" /> Público</span>
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">Cualquier persona, incluso sin iniciar sesión, podrá ver el código, el historial, las issues y los pull requests.</span>
                            </span>
                        </label>
                    </div>
                    @unless ($repository->isPublic())
                        <p class="mt-2 hidden rounded-lg border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-3 py-2 text-xs text-amber-800 dark:text-amber-300 [fieldset:has(input[value=public]:checked)_&]:block">
                            <strong>Atención:</strong> al hacerlo público, todo el contenido actual y su historial completo quedarán visibles para cualquiera. Revisa que no haya contraseñas, claves ni datos personales.
                        </p>
                    @endunless
                </fieldset>

                <div class="flex justify-end border-t border-zinc-200 dark:border-zinc-800 pt-4">
                    <x-button>Guardar cambios</x-button>
                </div>
            </form>
        </x-card>

        <section class="rounded-lg border border-rose-300 dark:border-rose-500/30 bg-white dark:bg-zinc-900 shadow-sm">
            <header class="border-b border-rose-200 dark:border-rose-500/30 px-4 py-3">
                <h2 class="text-sm font-semibold text-rose-700 dark:text-rose-300">Zona de peligro</h2>
            </header>
            <form method="POST" action="{{ route('repositories.destroy', $repository) }}" class="space-y-4 p-4 sm:p-6">
                @csrf
                @method('DELETE')
                <div>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Eliminar este repositorio</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Irá a la papelera: podrás restaurarlo durante 30 días desde tu menú › Papelera. Después se borrarán definitivamente el código, las ramas, los commits, las issues y los pull requests, y su clave de cifrado se destruye.</p>
                </div>
                <x-input name="confirm_name" autocomplete="off" class="font-mono">
                    <x-slot:label>Escribe <span class="font-mono font-semibold">{{ $repository->name }}</span> para confirmar</x-slot:label>
                </x-input>
                <x-button variant="danger"><x-code-icon name="trash" /> Eliminar repositorio</x-button>
            </form>
        </section>
    </div>
</x-app-layout>
