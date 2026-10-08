<x-app-layout title="Mi perfil">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Mi perfil</h1>
        <p class="text-sm text-slate-500">Gestiona tus datos personales y tu contraseña.</p>
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
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input label="Contraseña nueva" name="password" type="password" required minlength="8" autocomplete="new-password" help="Mínimo 8 caracteres." />
                        <x-input label="Repite la contraseña nueva" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" />
                    </div>
                    <p class="text-xs text-slate-500">Al cambiarla se cerrará la sesión en tus otros dispositivos (navegadores y app Android).</p>
                    <div class="flex justify-end"><x-button>Cambiar contraseña</x-button></div>
                </form>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Mi cuenta">
                <dl class="space-y-3 p-4 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Tipo de cuenta</dt><dd>@if ($user->isAdmin())<x-badge color="amber">Administrador</x-badge>@else<x-badge>Usuario</x-badge>@endif</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Miembro desde</dt><dd class="text-slate-800">{{ $user->created_at?->translatedFormat('j M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Sesiones en la app</dt><dd class="text-slate-800">{{ $tokens }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Mis roles">
                @if ($memberships->isEmpty())
                    <x-empty title="No participas en ningún proyecto" class="py-8" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($memberships as $membership)
                            <li class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                                <a href="{{ route('projects.show', $membership->project) }}" class="min-w-0 truncate text-indigo-700 hover:underline">{{ $membership->project->name }}</a>
                                <x-account-role-badge :role="$membership->role" />
                            </li>
                        @endforeach
                    </ul>
                    <div class="flex flex-wrap gap-2 border-t border-slate-100 px-4 py-3 text-xs text-slate-500">
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
