@props([
    'label' => null,
    'name' => null,
])

<div>
    @if($label)
        <label class="mb-1.5 block text-sm font-medium text-zinc-300">{{ $label }}</label>
    @endif

    <input
        type="date"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-zinc-600/60 bg-zinc-800/80 px-3 py-2 text-sm text-white transition-all focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30']) }}
    />

    @error($name ?? $attributes->get('wire:model', ''))
        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
    @enderror
</div>
