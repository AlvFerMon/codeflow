<x-app-layout title="Nuevo repositorio">
    <div class="mx-auto max-w-2xl">
        <nav class="mb-2 text-sm">
            <a href="{{ route('projects.show', $project) }}" class="text-purple-600 dark:text-purple-400 hover:underline">{{ $project->name }}</a>
            <span class="text-zinc-500 dark:text-zinc-400">/</span>
            <span class="text-zinc-600 dark:text-zinc-400">Nuevo repositorio</span>
        </nav>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Crear un repositorio</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Un repositorio guarda el código del proyecto con su historial de versiones, ramas y commits.</p>

        <x-card class="mt-6">
            <form method="POST" action="{{ route('repositories.store', $project) }}" class="space-y-5 p-4 sm:p-6">
                @csrf
                <x-input name="name" label="Nombre del repositorio" required maxlength="100" autofocus
                    pattern="[A-Za-z0-9._\-]+" placeholder="mi-repositorio"
                    help="Letras, números, puntos, guiones y guiones bajos. Debe ser único dentro del proyecto." />

                <x-textarea name="description" label="Descripción (opcional)" rows="3" maxlength="2000" placeholder="¿De qué trata este repositorio?" />

                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">Visibilidad</legend>
                    @php($visibility = old('visibility', 'private'))
                    <div class="space-y-2">
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 has-[:checked]:border-purple-400 has-[:checked]:bg-purple-50/50 dark:has-[:checked]:border-purple-500/60 dark:has-[:checked]:bg-purple-500/10">
                            <input type="radio" name="visibility" value="private" class="mt-1" @checked($visibility === 'private')>
                            <span>
                                <span class="flex items-center gap-1.5 text-sm font-medium text-zinc-800 dark:text-zinc-200"><x-code-icon name="lock" /> Privado</span>
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">Solo los miembros del proyecto pueden ver el código. Recomendado.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-zinc-200 dark:border-zinc-800 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 has-[:checked]:border-purple-400 has-[:checked]:bg-purple-50/50 dark:has-[:checked]:border-purple-500/60 dark:has-[:checked]:bg-purple-500/10">
                            <input type="radio" name="visibility" value="public" class="mt-1" @checked($visibility === 'public')>
                            <span>
                                <span class="flex items-center gap-1.5 text-sm font-medium text-zinc-800 dark:text-zinc-200"><x-code-icon name="globe" /> Público</span>
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">Cualquier persona, incluso sin iniciar sesión, puede ver el código, las ramas, los commits, las issues y los pull requests. Solo los miembros pueden modificarlo.</span>
                            </span>
                        </label>
                    </div>
                    @error('visibility')
                        <span class="mt-1 block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span>
                    @enderror
                </fieldset>

                <label class="flex items-start gap-3">
                    <input type="checkbox" name="init_readme" value="1" class="mt-1" @checked(old('init_readme', ! session()->hasOldInput()))>
                    <span>
                        <span class="block text-sm font-medium text-zinc-800 dark:text-zinc-200">Inicializar con un README</span>
                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">Crea un primer commit en la rama <code class="font-mono">main</code> con un archivo README.md.</span>
                    </span>
                </label>

                <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-200 dark:border-zinc-800 pt-4">
                    <x-button variant="secondary" :href="route('projects.show', $project)">Cancelar</x-button>
                    <x-button>Crear repositorio</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
