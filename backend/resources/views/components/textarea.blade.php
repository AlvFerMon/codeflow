@props(['label' => null, 'name', 'value' => null, 'rows' => 4])
<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
    @endif
    <textarea name="{{ $name }}" rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs placeholder-zinc-400 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder-zinc-500 dark:focus:border-purple-400']) }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <span class="mt-1 block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span>
    @enderror
</label>
