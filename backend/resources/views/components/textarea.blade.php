@props(['label' => null, 'name', 'value' => null, 'rows' => 4])
<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</span>
    @endif
    <textarea name="{{ $name }}" rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500']) }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>
    @enderror
</label>
