<x-app-layout :title="'Miembros · '.$project->name">
    @include('projects._header', ['project' => $project, 'active' => 'members'])
    <x-help summary="¿Qué puede hacer cada rol?" class="-mt-2 mb-4">
        El <strong>propietario</strong> gestiona el proyecto, sus repositorios y sus miembros. Los <strong>colaboradores</strong> pueden subir código,
        crear ramas, issues y pull requests. Los <strong>observadores</strong> solo pueden ver y escribir comentarios, que llegan al equipo como alertas.
    </x-help>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card :title="$members->total().' '.($members->total() === 1 ? 'miembro' : 'miembros').($q !== '' ? ' para «'.$q.'»' : '')">
                <x-slot:actions>
                    <form method="GET" action="{{ route('members.index', $project) }}" role="search" class="flex gap-1.5">
                        <label for="member-q" class="sr-only">Buscar miembro</label>
                        <input id="member-q" type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Nombre o email…"
                            class="w-40 rounded-lg border border-zinc-300 bg-white px-2.5 py-1 text-xs shadow-xs focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 sm:w-52 dark:border-zinc-700 dark:bg-zinc-900">
                        <x-button size="sm" variant="secondary">Buscar</x-button>
                    </form>
                </x-slot:actions>
                @if ($members->isEmpty())
                    <x-empty title="Ningún miembro coincide con «{{ $q }}»">Prueba con otro nombre o email.</x-empty>
                @endif
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($members as $member)
                        @php($isOwner = $member->role === \App\Support\Roles::OWNER)
                        <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-zinc-200 dark:bg-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ mb_strtoupper(mb_substr($member->user?->name ?? '?', 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        <span class="truncate">{{ $member->user?->name ?? 'Usuario eliminado' }}</span>
                                        <x-account-role-badge :role="$member->role" />
                                        @if ($member->user_id === auth()->id())<span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">(tú)</span>@endif
                                    </p>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $member->user?->email }} · desde {{ $member->created_at?->translatedFormat('j M Y') }}</p>
                                </div>
                            </div>

                            @can('manageMembers', $project)
                                @if (! $isOwner && $member->user)
                                    <div class="flex items-center gap-2 sm:shrink-0">
                                        <form method="POST" action="{{ route('members.update', [$project, $member->user]) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <label class="sr-only" for="role-{{ $member->user_id }}">Rol de {{ $member->user->name }}</label>
                                            <select id="role-{{ $member->user_id }}" name="role" class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-2 py-1.5 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500">
                                                @foreach ($roleOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <x-button variant="secondary" class="px-2.5! py-1.5!">Cambiar</x-button>
                                        </form>
                                        <form method="POST" action="{{ route('members.destroy', [$project, $member->user]) }}"
                                            data-confirm-title="Quitar del proyecto" data-confirm-button="Quitar" data-confirm="{{ $member->user->name }} dejará de ser miembro y perderá el acceso a los repositorios privados del proyecto.">
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
                <x-pagination :paginator="$members" />
            </x-card>
        </div>

        <aside>
            @can('manageMembers', $project)
                <x-card title="Añadir miembro">
                    <form method="POST" action="{{ route('members.store', $project) }}" class="space-y-4 p-4">
                        @csrf
                        <x-input label="Correo" name="email" type="email" required placeholder="usuario@ejemplo.com" help="Si aún no tiene cuenta, le enviaremos una invitación por correo." />
                        <x-select label="Rol" name="role" :options="$roleOptions" value="collaborator" />
                        <x-button class="w-full">Añadir o invitar</x-button>
                    </form>
                </x-card>

                @if ($invitations->isNotEmpty())
                    <x-card title="Invitaciones pendientes" class="mt-6">
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($invitations as $invitation)
                                <li class="flex items-center justify-between gap-3 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $invitation->email }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ ucfirst(\App\Support\Roles::label($invitation->role)) }} · caduca {{ $invitation->expires_at->diffForHumans() }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('invitations.destroy', [$project, $invitation]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-button variant="ghost" size="sm" title="Cancelar la invitación a {{ $invitation->email }}">Cancelar</x-button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif
            @else
                <x-card>
                    <div class="p-4 text-sm text-zinc-600 dark:text-zinc-400">
                        Solo el propietario del proyecto puede añadir miembros o cambiar sus roles.
                    </div>
                </x-card>
            @endcan
        </aside>
    </div>
</x-app-layout>
