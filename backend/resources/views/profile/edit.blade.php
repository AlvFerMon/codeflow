<x-app-layout title="Mi perfil">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Mi perfil</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Gestiona tus datos personales y tu contraseña.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Datos personales">
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 p-4 sm:p-6">
                    @csrf
                    @method('PUT')
                    <x-input label="Nombre" name="name" :value="$user->name" required maxlength="100" autocomplete="name" />
                    <x-input label="Correo electrónico" name="email" type="email" :value="$user->email" required autocomplete="email" />
                    <div class="flex justify-end"><x-button>Guardar datos</x-button></div>
                </form>
            </x-card>

            <x-card title="Cambiar contraseña">
                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4 p-4 sm:p-6">
                    @csrf
                    @method('PUT')
                    <x-input label="Contraseña actual" name="current_password" type="password" required autocomplete="current-password" />
                    <div class="space-y-4">
                        <x-password-strength label="Contraseña nueva" name="password" required minlength="8" autocomplete="new-password" />
                        <x-input label="Repite la contraseña nueva" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" />
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Al cambiarla se cerrará la sesión en tus otros dispositivos (navegadores y app Android).</p>
                    <div class="flex justify-end"><x-button>Cambiar contraseña</x-button></div>
                </form>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Mi cuenta">
                <dl class="space-y-3 p-4 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500 dark:text-zinc-400">Tipo de cuenta</dt><dd>@if ($user->isAdmin())<x-badge color="amber">Administrador</x-badge>@else<x-badge>Usuario</x-badge>@endif</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500 dark:text-zinc-400">Miembro desde</dt><dd class="text-zinc-800 dark:text-zinc-200">{{ $user->created_at?->translatedFormat('j M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500 dark:text-zinc-400">Sesiones en la app</dt><dd class="text-zinc-800 dark:text-zinc-200">{{ $tokens }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Mis roles">
                @if ($memberships->isEmpty())
                    <x-empty title="No participas en ningún proyecto" class="py-8" />
                @else
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($memberships as $membership)
                            <li class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                                <a href="{{ route('projects.show', $membership->project) }}" class="min-w-0 truncate text-purple-700 dark:text-purple-300 hover:underline">{{ $membership->project->name }}</a>
                                <x-account-role-badge :role="$membership->role" />
                            </li>
                        @endforeach
                    </ul>
                    <div class="flex flex-wrap gap-2 border-t border-zinc-100 dark:border-zinc-800 px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                        @foreach ([\App\Support\Roles::OWNER, \App\Support\Roles::COLLABORATOR, \App\Support\Roles::OBSERVER] as $r)
                            @php($n = $memberships->where('role', $r)->count())
                            @if ($n)<span>{{ $n }} × {{ \App\Support\Roles::label($r) }}</span>@endif
                        @endforeach
                    </div>
                @endif
            </x-card>
        </aside>
    </div>
</x-app-layout>
