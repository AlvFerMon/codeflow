<x-app-layout title="Notificaciones">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Notificaciones</h1>
            <p class="text-sm text-slate-500">
                {{ $unreadCount === 0 ? 'Estás al día.' : ($unreadCount === 1 ? 'Tienes 1 notificación sin leer.' : "Tienes {$unreadCount} notificaciones sin leer.") }}
            </p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <x-button variant="secondary">Marcar todas como leídas</x-button>
            </form>
        @endif
    </div>

    <nav class="mb-4 flex gap-1 border-b border-slate-200 text-sm">
        @foreach (['all' => ['Todas', route('notifications.index')], 'unread' => ['No leídas', route('notifications.index', ['filter' => 'unread'])]] as $key => [$label, $url])
            @php($active = ($key === 'unread') === $unreadOnly)
            <a href="{{ $url }}" @class([
                '-mb-px flex items-center gap-1.5 border-b-2 px-3 py-2',
                'border-indigo-500 font-medium text-slate-900' => $active,
                'border-transparent text-slate-600 hover:text-slate-900' => ! $active,
            ])>
                {{ $label }}
                @if ($key === 'unread' && $unreadCount > 0)
                    <span class="rounded-full bg-indigo-100 px-1.5 text-xs font-semibold text-indigo-700">{{ $unreadCount }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <x-card>
        @if ($notifications->isEmpty())
            <x-empty :title="$unreadOnly ? 'No tienes notificaciones sin leer' : 'No tienes notificaciones'">
                Aquí verás los cambios en los proyectos de los que formas parte.
            </x-empty>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($notifications as $notification)
                    @php($data = $notification->data)
                    @php($isAlert = ($data['kind'] ?? null) === 'alert')
                    @php($unread = $notification->read_at === null)
                    <li>
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" @class([
                                'flex w-full gap-3 border-l-4 px-4 py-4 text-left transition hover:bg-slate-50',
                                'border-rose-400 bg-rose-50/70 hover:bg-rose-50' => $isAlert,
                                'border-indigo-400' => ! $isAlert && $unread,
                                'border-transparent' => ! $isAlert && ! $unread,
                            ])>
                                <x-account-notification-icon :event="$data['event'] ?? null" :alert="$isAlert" />
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span @class(['text-sm', 'font-semibold text-slate-900' => $unread, 'font-medium text-slate-600' => ! $unread])>{{ $data['title'] ?? 'Notificación' }}</span>
                                        @if ($isAlert)
                                            <x-badge color="red">Alerta</x-badge>
                                        @endif
                                        @if ($unread)
                                            <span class="h-2 w-2 rounded-full bg-indigo-500" title="No leída"></span>
                                            <span class="sr-only">No leída</span>
                                        @endif
                                    </span>
                                    <span @class(['mt-1 block break-words text-sm', 'text-slate-800' => $unread, 'text-slate-500' => ! $unread])>{{ $data['message'] ?? '' }}</span>
                                    <span class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-slate-500">
                                        @if (! empty($data['actor']['name']))<span>Por {{ $data['actor']['name'] }}</span>@endif
                                        @if (! empty($data['project_name']))<span>Proyecto: {{ $data['project_name'] }}</span>@endif
                                        <time datetime="{{ $notification->created_at?->toIso8601String() }}" title="{{ $notification->created_at?->format('d/m/Y H:i') }}">{{ $notification->created_at?->diffForHumans() }}</time>
                                    </span>
                                </span>
                                <svg class="mt-2 h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
            <x-account-pagination :paginator="$notifications" />
        @endif
    </x-card>
</x-app-layout>
