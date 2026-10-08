@props(['label' => null, 'name', 'options' => [], 'value' => null, 'placeholder' => null])
<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</span>
    @endif
    <select name="{{ $name }}" {{ $attributes->merge(['class' => 'block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500']) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>
    @enderror
</label>
