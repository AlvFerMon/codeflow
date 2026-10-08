<x-app-layout title="Inicio">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Hola, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-slate-500">Este es el resumen de tus proyectos.</p>
        </div>
        @can('create', \App\Models\Project::class)
            <x-button :href="route('projects.create')">+ Nuevo proyecto</x-button>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Mis proyectos</h2>
                @if ($projects->isNotEmpty())
                    <a href="{{ route('projects.index') }}" class="text-sm text-indigo-600 hover:underline">Ver todos</a>
                @endif
            </div>
            @if ($projects->isEmpty())
                <x-card>
                    <x-empty title="Todavía no participas en ningún proyecto">
                        Crea uno nuevo o pide al propietario de un proyecto que te dé acceso.
                        <div class="mt-4"><x-button :href="route('projects.create')">Crear mi primer proyecto</x-button></div>
                    </x-empty>
                </x-card>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($projects as $project)
                        <x-account-project-card :project="$project" />
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="space-y-6">
            <x-card title="Notificaciones recientes">
                <x-slot:actions>
                    <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Ver todas</a>
                </x-slot:actions>
                @forelse ($notifications as $notification)
                    @php($data = $notification->data)
                    @php($isAlert = ($data['kind'] ?? null) === 'alert')
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="border-b border-slate-100 last:border-b-0">
                        @csrf
                        <button type="submit" @class([
                            'flex w-full gap-3 px-4 py-3 text-left hover:bg-slate-50',
                            'border-l-4 border-rose-400 bg-rose-50/60' => $isAlert,
                        ])>
                            <x-account-notification-icon :event="$data['event'] ?? null" :alert="$isAlert" class="h-7! w-7!" />
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span @class(['truncate text-sm', 'font-semibold text-slate-900' => ! $notification->read_at, 'text-slate-700' => $notification->read_at])>{{ $data['title'] ?? 'Notificación' }}</span>
                                    @if ($isAlert)<x-badge color="red">Alerta</x-badge>@endif
                                    @unless ($notification->read_at)<span class="ml-auto h-2 w-2 shrink-0 rounded-full bg-indigo-500" title="No leída"></span>@endunless
                                </span>
                                <span class="mt-0.5 line-clamp-2 block text-xs text-slate-600">{{ $data['message'] ?? '' }}</span>
                                <span class="mt-0.5 block text-[11px] text-slate-400">{{ $notification->created_at?->diffForHumans() }}</span>
                            </span>
                        </button>
                    </form>
                @empty
                    <x-empty title="Sin notificaciones" class="py-8" />
                @endforelse
            </x-card>

            <x-card title="Actividad reciente">
                @if ($activity->isEmpty())
                    <x-empty title="Aún no hay actividad" class="py-8" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($activity as $item)
                            <x-account-activity-item :item="$item" :project-name="$projectNames[$item['project_id']] ?? null" :project-url="route('projects.show', $item['project_id'])" />
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </aside>
    </div>
</x-app-layout>
