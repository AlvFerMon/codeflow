<x-app-layout title="Mi perfil">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Mi perfil</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Gestiona tus datos personales, tu contraseña y la accesibilidad.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Datos personales">
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 p-4 sm:p-6">
                    @csrf
                    @method('PUT')
                    <x-input label="Nombre" name="name" :value="$user->name" required maxlength="100" autocomplete="name" />
                    <x-input label="Correo electrónico" name="email" type="email" :value="$user->email" required autocomplete="email" />
                    <x-input label="Contraseña actual" name="email_password" type="password" autocomplete="current-password"
                        help="Solo si cambias el correo. Después tendrás que verificar el correo nuevo." />
                    <div class="flex justify-end"><x-button>Guardar datos</x-button></div>
                </form>
            </x-card>

            <x-card title="Accesibilidad y apariencia" subtitle="Se guardan en tu cuenta: se aplican en cualquier navegador y en las apps.">
                @php($prefs = \App\Support\Preferences::for($user))
                <form method="POST" action="{{ route('profile.preferences') }}" class="space-y-5 p-4 sm:p-6">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_form" value="1">

                    <fieldset>
                        <legend class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Tema</legend>
                        <div class="mt-2 flex flex-wrap gap-4 text-sm">
                            @foreach (['system' => 'Como el sistema', 'light' => 'Claro', 'dark' => 'Oscuro'] as $value => $label)
                                <label class="inline-flex items-center gap-2"><input type="radio" name="theme" value="{{ $value }}" @checked($prefs['theme'] === $value) class="accent-[#FF3366]"> {{ $label }}</label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Tamaño de letra</legend>
                        <div class="mt-2 flex flex-wrap gap-4">
                            @foreach (['normal' => ['Normal', 'text-sm'], 'large' => ['Grande', 'text-base'], 'larger' => ['Muy grande', 'text-lg']] as $value => [$label, $size])
                                <label class="inline-flex items-center gap-2 {{ $size }}"><input type="radio" name="font_size" value="{{ $value }}" @checked($prefs['font_size'] === $value) class="accent-[#FF3366]"> {{ $label }}</label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="space-y-3 text-sm">
                        @foreach ([
                            'high_contrast' => ['Alto contraste', 'Textos secundarios y bordes más marcados.'],
                            'reduce_motion' => ['Reducir animaciones', 'Quita transiciones y movimientos de la interfaz.'],
                            'underline_links' => ['Subrayar los enlaces', 'Distingue los enlaces sin depender del color.'],
                            'wrap_code' => ['Ajustar líneas largas de código', 'El código y los cambios saltan de línea en vez de desplazarse en horizontal.'],
                        ] as $key => [$label, $help])
                            <label class="flex items-start gap-3">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked($prefs[$key]) class="mt-1 h-4 w-4 accent-[#FF3366]">
                                <span><span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $label }}</span><span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $help }}</span></span>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex justify-end"><x-button>Guardar preferencias</x-button></div>
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
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500 dark:text-zinc-400">Apps conectadas</dt><dd class="text-zinc-800 dark:text-zinc-200">{{ $tokens }}</dd></div>
                    <div class="pt-1"><a href="{{ route('profile.devices') }}" class="text-sm font-semibold text-purple-700 hover:underline dark:text-purple-300">Ver dispositivos y sesiones abiertas →</a></div>
                </dl>
            </x-card>

            <x-card title="Tus datos" subtitle="Lo que CodeFlow guarda de ti, a tu alcance.">
                <div class="space-y-4 p-4 text-sm">
                    <div>
                        <p class="text-zinc-600 dark:text-zinc-400">Descarga en un archivo todo lo que hay asociado a tu cuenta: perfil, proyectos, commits, issues, comentarios, actividad y dispositivos.</p>
                        <x-button variant="secondary" size="sm" :href="route('profile.export')" class="mt-2">Descargar mis datos</x-button>
                    </div>

                    @php($blockers = app(\App\Services\AccountService::class)->blockers($user))
                    @php($bag = $errors->getBag('deleteAccount'))
                    <details class="border-t border-zinc-100 pt-4 dark:border-zinc-800" @if ($bag->any()) open @endif>
                        <summary class="cursor-pointer font-semibold text-rose-700 dark:text-rose-300">Borrar mi cuenta</summary>
                        <div class="mt-3 space-y-3">
                            <p class="text-zinc-600 dark:text-zinc-400">Se borran tu cuenta, tus sesiones y los proyectos en los que eres el único miembro. En los proyectos compartidos sales del proyecto y lo que escribiste se queda como «usuario eliminado». No se puede deshacer.</p>
                            @if ($blockers)
                                <p class="rounded-lg bg-amber-50 p-3 text-amber-900 dark:bg-amber-500/10 dark:text-amber-200" role="alert">
                                    Antes, nombra otro propietario (o borra el proyecto) en: <strong>{{ implode(', ', $blockers) }}</strong>.
                                </p>
                            @else
                                <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-3">
                                    @csrf
                                    @method('DELETE')
                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Contraseña</span>
                                        <input type="password" name="password" required autocomplete="current-password" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                        @if ($bag->has('password'))<span class="mt-1 block text-xs text-rose-600 dark:text-rose-400">{{ $bag->first('password') }}</span>@endif
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Escribe BORRAR para confirmar</span>
                                        <input type="text" name="confirm" required autocomplete="off" pattern="BORRAR" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                        @if ($bag->has('confirm'))<span class="mt-1 block text-xs text-rose-600 dark:text-rose-400">{{ $bag->first('confirm') }}</span>@endif
                                    </label>
                                    <x-button variant="danger" class="w-full">Borrar mi cuenta para siempre</x-button>
                                </form>
                            @endif
                        </div>
                    </details>
                </div>
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
