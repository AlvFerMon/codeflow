{{--
    Selector de rama (formulario GET). Envía ?branch= a la URL indicada conservando los parámetros extra.
    branches: lista de nombres; current: nombre de la rama actual; params: [name => value] ocultos.
--}}
@props(['action', 'branches' => [], 'current' => null, 'params' => []])
<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'flex items-center gap-1']) }}>
    @foreach ($params as $paramName => $paramValue)
        @if ($paramValue !== null && $paramValue !== '')
            <input type="hidden" name="{{ $paramName }}" value="{{ $paramValue }}">
        @endif
    @endforeach
    <label class="relative flex items-center">
        <span class="sr-only">Rama</span>
        <x-code-icon name="branch" class="pointer-events-none absolute left-2.5 h-4 w-4 text-slate-500" />
        <select name="branch" onchange="this.form.submit()"
            class="max-w-[14rem] truncate rounded-md border border-slate-300 bg-white py-1.5 pl-8 pr-8 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            @foreach ($branches as $name)
                <option value="{{ $name }}" @selected($name === $current)>{{ $name }}</option>
            @endforeach
        </select>
    </label>
    <noscript><x-button variant="secondary" class="py-1.5">Ir</x-button></noscript>
</form>
