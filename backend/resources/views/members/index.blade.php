<x-app-layout :title="'Miembros · '.$project->name">
    <div class="mb-6">
        <a href="{{ route('projects.show', $project) }}" class="text-sm text-slate-500 hover:underline">← {{ $project->name }}</a>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Miembros del proyecto</h1>
        <p class="text-sm text-slate-500">
            Los <strong>colaboradores</strong> pueden subir código, crear ramas, issues y pull requests.
            Los <strong>observadores</strong> solo pueden ver y comentar.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card :title="$members->count().' '.($members->count() === 1 ? 'miembro' : 'miembros')">
                <ul class="divide-y divide-slate-100">
                    @foreach ($members as $member)
                        @php($isOwner = $member->role === \App\Support\Roles::OWNER)
                        <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">{{ mb_strtoupper(mb_substr($member->user?->name ?? '?', 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-900">
                                        <span class="truncate">{{ $member->user?->name ?? 'Usuario eliminado' }}</span>
                                        <x-account-role-badge :role="$member->role" />
                                        @if ($member->user_id === auth()->id())<span class="text-xs font-normal text-slate-400">(tú)</span>@endif
                                    </p>
                                    <p class="truncate text-xs text-slate-500">{{ $member->user?->email }} · desde {{ $member->created_at?->translatedFormat('j M Y') }}</p>
                                </div>
                            </div>

                            @can('manageMembers', $project)
                                @if (! $isOwner && $member->user)
                                    <div class="flex items-center gap-2 sm:shrink-0">
                                        <form method="POST" action="{{ route('members.update', [$project, $member->user]) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <label class="sr-only" for="role-{{ $member->user_id }}">Rol de {{ $member->user->name }}</label>
                                            <select id="role-{{ $member->user_id }}" name="role" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                @foreach ($roleOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <x-button variant="secondary" class="px-2.5! py-1.5!">Cambiar</x-button>
                                        </form>
                                        <form method="POST" action="{{ route('members.destroy', [$project, $member->user]) }}"
                                            onsubmit="return confirm(@js('¿Quitar a '.$member->user->name.' del proyecto? Perderá el acceso a sus repositorios privados.'))">
                                            @csrf
                                            @method('DELETE')
                                            <x-button variant="ghost" class="px-2.5! py-1.5! text-rose-600! hover:bg-rose-50!">Quitar</x-button>
                                        </form>
                                    </div>
                                @endif
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>

        <aside>
            @can('manageMembers', $project)
                <x-card title="Añadir miembro">
                    <form method="POST" action="{{ route('members.store', $project) }}" class="space-y-4 p-4">
                        @csrf
                        <x-input label="Correo del usuario" name="email" type="email" required placeholder="usuario@ejemplo.com" help="Debe tener ya una cuenta en CodeFlow." />
                        <x-select label="Rol" name="role" :options="$roleOptions" value="collaborator" />
                        <x-button class="w-full">Añadir al proyecto</x-button>
                    </form>
                </x-card>
            @else
                <x-card>
                    <div class="p-4 text-sm text-slate-600">
                        Solo el propietario del proyecto puede añadir miembros o cambiar sus roles.
                    </div>
                </x-card>
            @endcan
        </aside>
    </div>
</x-app-layout>
