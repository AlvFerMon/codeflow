{{-- Tamaño legible en bytes (formato español: 1,5 KB) --}}
@props(['bytes' => 0])
<span {{ $attributes }}>{{ \Illuminate\Support\Number::withLocale(app()->getLocale(), fn () => \Illuminate\Support\Number::fileSize((int) $bytes, (int) $bytes >= 1024 ? 1 : 0, 1)) }}</span>
