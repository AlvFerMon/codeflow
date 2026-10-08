@props(['label' => null, 'name', 'type' => 'text', 'value' => null, 'help' => null])
<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</span>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : old($name, $value) }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500'.($errors->has($name) ? ' border-rose-400' : '')]) }}>
    @if ($help)
        <span class="mt-1 block text-xs text-slate-500">{{ $help }}</span>
    @endif
    @error($name)
        <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>
    @enderror
</label>
