{{-- Cabecera común de un repositorio. Variables: $repository, $active (code|issues|pulls|commits|branches|settings) --}}
<div class="mb-6">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-2xl">
        @can('view', $repository->project)
            <a href="{{ route('projects.show', $repository->project) }}" class="font-semibold text-zinc-500 hover:text-purple-600 dark:text-zinc-400 dark:hover:text-purple-300">{{ $repository->project->name }}</a>
        @else
            <span class="text-zinc-500 dark:text-zinc-400">{{ $repository->project->name }}</span>
        @endcan
        <span class="text-zinc-500 dark:text-zinc-400">/</span>
        <a href="{{ route('repositories.show', $repository) }}" class="font-extrabold tracking-tight text-zinc-950 hover:text-purple-600 dark:text-white dark:hover:text-purple-300">{{ $repository->name }}</a>
        <x-badge :color="$repository->isPublic() ? 'green' : 'purple'" class="self-center">{{ $repository->isPublic() ? 'Público' : 'Privado' }}</x-badge>
    </div>
    @if ($repository->description)
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $repository->description }}</p>
    @endif
    @guest
        @if ($repository->isPublic())
            <div class="mt-4 flex flex-col gap-3 ficha rounded-lg px-4 py-3 sm:flex-row sm:items-center">
                <p class="text-xs leading-5 text-zinc-700 dark:text-zinc-300">
                    <span class="font-semibold text-zinc-900 dark:text-white">Estás viendo un repositorio público.</span>
                    Puedes leer el código, descargar archivos y seguir sus issues y pull requests. Para comentar o proponer cambios necesitas una cuenta.
                </p>
                <div class="flex shrink-0 gap-2 sm:ml-auto">
                    <x-button variant="secondary" size="sm" :href="route('login')">Iniciar sesión</x-button>
                    <x-button variant="purple" size="sm" :href="route('register')">Crear cuenta</x-button>
                </div>
            </div>
        @endif
    @endguest
    @php
        $active = $active ?? 'code';
        $tabs = [
            'code' => ['label' => 'Código', 'url' => route('repositories.show', $repository), 'tour' => 'pestana-codigo'],
            'issues' => ['label' => 'Issues', 'url' => route('issues.index', $repository), 'tour' => 'pestana-issues'],
            'pulls' => ['label' => 'Pull requests', 'url' => route('pulls.index', $repository), 'tour' => 'pestana-pulls'],
            'commits' => ['label' => 'Commits', 'url' => route('commits.index', $repository), 'tour' => 'pestana-commits'],
            'branches' => ['label' => 'Ramas', 'url' => route('branches.index', $repository), 'tour' => 'pestana-ramas'],
        ];
        if (auth()->user()?->can('update', $repository)) {
            $tabs['settings'] = ['label' => 'Configuración', 'url' => route('repositories.settings', $repository), 'end' => true];
        }
    @endphp
    <x-tabs class="mt-4" label="Secciones del repositorio" :active="$active" :items="$tabs" />
    @php
        $helps = [
            'code' => ['¿Qué es un repositorio?', 'Un repositorio guarda el código de un programa y todo su historial. Puedes navegar por las carpetas, abrir archivos y cambiar de rama con el selector.'],
            'issues' => ['¿Qué es una issue?', 'Una issue es una tarea, un error o una idea que hay que resolver. Sirve para que el equipo sepa qué falta por hacer, quién se encarga y con qué prioridad.'],
            'pulls' => ['¿Qué es un pull request?', 'Un pull request es una propuesta para llevar los cambios de una rama a otra (por ejemplo, de «feature/login» a «main»). El equipo los revisa, comenta y, si todo está bien, los fusiona.'],
            'commits' => ['¿Qué es un commit?', 'Un commit es una «foto» guardada del código en un momento dado, con un mensaje que explica qué cambió. La lista muestra el historial de la rama, del más reciente al más antiguo.'],
            'branches' => ['¿Qué es una rama?', 'Una rama es una copia independiente del código para trabajar sin afectar a la versión principal. Cuando el trabajo está listo, se propone fusionarla con un pull request.'],
        ];
    @endphp
    @isset($helps[$active])
        <x-help :summary="$helps[$active][0]" class="mt-3">{{ $helps[$active][1] }}</x-help>
    @endisset
</div>
