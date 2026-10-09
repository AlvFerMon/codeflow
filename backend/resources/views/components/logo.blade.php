{{-- Logo de CodeFlow: monograma "CF" (la F es una rama con commits). La C usa currentColor para adaptarse al modo oscuro.
     Props: wordmark (muestra el texto "CodeFlow"), size (clases de alto/ancho del símbolo). --}}
@props(['wordmark' => true, 'size' => 'h-9 w-9', 'text' => 'text-base'])
@php($gid = 'cf-'.\Illuminate\Support\Str::random(6))
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-zinc-950 dark:text-white']) }}>
    <svg class="{{ $size }} shrink-0" viewBox="0 0 64 64" aria-hidden="true">
        <defs><linearGradient id="{{ $gid }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#9810FA"/><stop offset="1" stop-color="#4F39F6"/></linearGradient></defs>
        <g stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none">
            <path d="M30 14a18 18 0 1 0 0 36" stroke="currentColor"/>
            <path d="M38 50V14h14M38 32h10" stroke="url(#{{ $gid }})"/>
        </g>
        <circle cx="52" cy="14" r="4.5" fill="#9810FA"/><circle cx="48" cy="32" r="4.5" fill="#4F39F6"/>
    </svg>
    @if ($wordmark)
        <span class="{{ $text }} font-bold tracking-tight">Code<span class="text-purple-600 dark:text-purple-400">Flow</span></span>
    @endif
</span>
