<x-app-layout title="Dispositivos y sesiones">
    <div class="mb-6 flex flex-col justify-between gap-3 border-b border-zinc-200/80 pb-6 sm:flex-row sm:items-end dark:border-zinc-800">
        <div>
            <p class="text-sm"><a href="{{ route('profile.edit') }}" class="text-zinc-500 hover:underline dark:text-zinc-400">Mi perfil</a> /</p>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">Dispositivos y sesiones</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Dónde tienes abierta tu cuenta. Si no reconoces alguno, ciérralo y cambia tu contraseña.</p>
        </div>
        @if ($apps->isNotEmpty() || $web->where('current', false)->isNotEmpty())
            <form method="POST" action="{{ route('profile.devices.others') }}"
                data-confirm-title="Cerrar las demás sesiones" data-confirm-button="Cerrar todas"
                data-confirm="Se cerrará tu cuenta en todos los demás navegadores y apps (CLI, escritorio, Android). Esta sesión sigue abierta.">
                @csrf
                @method('DELETE')
                <x-button variant="secondary" size="sm">Cerrar todas las demás</x-button>
            </form>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Navegadores">
            @if (! $web_supported)
                <p class="p-4 text-sm text-zinc-500 dark:text-zinc-400">En este servidor las sesiones del navegador no se guardan en la base de datos, así que no se pueden listar.</p>
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($web as $s)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $s['name'] }}
                                    @if ($s['current'])<x-badge variant="teal" size="xs">este navegador</x-badge>@endif
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $s['ip'] ?? 'IP desconocida' }} · activo {{ $s['last_active_at']->diffForHumans() }}</p>
                            </div>
                            @unless ($s['current'])
                                <form method="POST" action="{{ route('profile.devices.session', $s['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button variant="ghost" size="sm" title="Cerrar la sesión de {{ $s['name'] }}">Cerrar</x-button>
                                </form>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card title="Apps (escritorio, terminal y Android)">
            @if ($apps->isEmpty())
                <x-empty title="No hay apps conectadas">Cuando inicies sesión en la app de escritorio, la terminal o el móvil aparecerán aquí. <a href="{{ route('downloads') }}" class="font-semibold underline">Descargas</a></x-empty>
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($apps as $a)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $a['name'] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">conectada {{ $a['created_at']?->diffForHumans() }} · último uso {{ $a['last_active_at']?->diffForHumans() }}</p>
                            </div>
                            <form method="POST" action="{{ route('profile.devices.token', $a['id']) }}">
                                @csrf
                                @method('DELETE')
                                <x-button variant="ghost" size="sm" title="Cerrar la sesión de {{ $a['name'] }}">Cerrar</x-button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-app-layout>
