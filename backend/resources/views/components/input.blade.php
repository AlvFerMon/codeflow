@props(['label' => null, 'name', 'type' => 'text', 'value' => null, 'help' => null])
<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : old($name, $value) }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs placeholder-zinc-400 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500/20 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder-zinc-500 dark:focus:border-purple-400 '.($errors->has($name) ? 'border-rose-400 dark:border-rose-500/60' : 'border-zinc-300 dark:border-zinc-700')]) }}>
    @if ($help)
        <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $help }}</span>
    @endif
    @error($name)
        <span class="mt-1 block text-xs text-rose-600 dark:text-rose-400">{{ $message }}</span>
    @enderror
</label>
