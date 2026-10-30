<x-app-layout :title="$title">
    <div class="mx-auto max-w-3xl">
        <nav class="mb-6 flex flex-wrap gap-2 text-sm" aria-label="Páginas legales">
            @foreach (\App\Http\Controllers\LegalController::PAGES as $slug => $name)
                <a href="{{ route('legal', $slug) }}" @if ($slug === $page) aria-current="page" @endif
                    class="rounded-full border px-3 py-1 {{ $slug === $page ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' : 'border-zinc-300 text-zinc-700 hover:border-zinc-500 dark:border-zinc-700 dark:text-zinc-300' }}">{{ $name }}</a>
            @endforeach
        </nav>
        <h1 class="mb-6 text-2xl font-bold tracking-tight text-zinc-950 dark:text-white">{{ $title }}</h1>
        <x-card class="p-5 sm:p-8">
            {{-- Texto de resources/legal/*.md, convertido con el HTML escapado --}}
            <div class="markdown legal break-words">{!! $html !!}</div>
        </x-card>
    </div>
</x-app-layout>
