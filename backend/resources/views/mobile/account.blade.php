<x-mobile-layout title="Cuenta" tab="account">
    <x-card class="p-4">
        <div class="flex items-center gap-3">
            <x-user-avatar :user="$user" size="h-12 w-12 text-base" />
            <div class="min-w-0">
                <p class="truncate font-semibold">{{ $user->name }}</p>
                <p class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
            </div>
        </div>
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">Miembro de {{ $projectsCount }} {{ $projectsCount === 1 ? 'proyecto' : 'proyectos' }}.</p>
    </x-card>

    <x-card class="mt-3 p-4">
        <p class="mb-2 text-xs font-medium tracking-wider text-zinc-500 uppercase dark:text-zinc-400">Tema</p>
        @include('layouts._theme-switch')
    </x-card>

    <div class="mt-3 space-y-2">
        <a href="{{ route('mobile.full') }}" class="block rounded-xl border border-zinc-200/80 bg-white px-4 py-3 text-sm font-medium dark:border-zinc-800 dark:bg-zinc-900">
            Abrir la versión completa
            <span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">Código, subidas, pull requests y configuración (mejor en el ordenador).</span>
        </a>
        <nav class="flex flex-wrap justify-center gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400" aria-label="Información legal">
            <a href="{{ route('legal', 'privacidad') }}" class="underline">Privacidad</a>
            <a href="{{ route('legal', 'cookies') }}" class="underline">Cookies</a>
            <a href="{{ route('legal', 'terminos') }}" class="underline">Condiciones</a>
            <a href="{{ route('legal', 'aviso-legal') }}" class="underline">Aviso legal</a>
        </nav>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full rounded-xl border border-rose-200 bg-white px-4 py-3 text-left text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:bg-zinc-900 dark:text-rose-400">Cerrar sesión</button>
        </form>
    </div>
</x-mobile-layout>
