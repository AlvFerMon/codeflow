<x-mobile-layout title="Avisos" tab="notifications">
    <div class="mb-3 flex items-center justify-between gap-2">
        <x-tabs variant="pills" label="Filtro" :active="$unreadOnly ? 'unread' : 'all'" :items="[
            'all' => ['label' => 'Todos', 'url' => route('mobile.notifications')],
            'unread' => ['label' => 'Sin leer', 'url' => route('mobile.notifications', ['filter' => 'unread'])],
        ]" />
        @if (auth()->user()->unreadNotificationsCount() > 0)
            <form method="POST" action="{{ route('mobile.notifications.readAll') }}">
                @csrf
                <button class="text-sm font-medium text-purple-600 dark:text-purple-400">Marcar todos</button>
            </form>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <x-card><x-empty :title="$unreadOnly ? 'No tienes avisos sin leer' : 'No tienes avisos'">Te avisaremos de los cambios en tus proyectos.</x-empty></x-card>
    @else
        <ul class="space-y-2">
            @foreach ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $unread = $notification->read_at === null;
                    $alert = ($data['kind'] ?? 'change') === 'alert';
                @endphp
                <li @class([
                    'rounded-xl border bg-white dark:bg-zinc-900',
                    'border-purple-200 dark:border-purple-500/30' => $unread,
                    'border-zinc-200/80 dark:border-zinc-800' => ! $unread,
                ])>
                    <form method="POST" action="{{ route('mobile.notifications.read', $notification->id) }}">
                        @csrf
                        <button class="flex w-full gap-3 px-4 py-3 text-left" @disabled(! $unread)>
                            <span @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-amber-500' => $unread && $alert, 'bg-purple-600' => $unread && ! $alert, 'bg-transparent' => ! $unread])></span>
                            <span class="min-w-0 flex-1">
                                <span @class(['block text-sm', 'font-semibold' => $unread, 'text-zinc-600 dark:text-zinc-400' => ! $unread])>{{ $data['title'] ?? 'Aviso' }}</span>
                                @if (! empty($data['message']))
                                    <span class="mt-0.5 block text-sm break-words text-zinc-600 dark:text-zinc-400">{{ $data['message'] }}</span>
                                @endif
                                <span class="mt-1 block text-[11px] text-zinc-500 dark:text-zinc-400">
                                    {{ collect([$data['project_name'] ?? null, $notification->created_at?->diffForHumans()])->filter()->join(' · ') }}
                                </span>
                            </span>
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
        @include('mobile._more', ['paginator' => $notifications])
    @endif
</x-mobile-layout>
