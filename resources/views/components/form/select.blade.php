@props([
    'label' => null,
    'options' => [],
    'placeholder' => null,
    'error' => null,
])

<div>
    @if($label)
        <label class="mb-1.5 block text-sm font-medium text-zinc-300">{{ $label }}</label>
    @endif

    <select {{ $attributes->merge(['class' => 'w-full rounded-lg border border-zinc-600/60 bg-zinc-800/80 px-3 py-2 text-sm text-white transition-all focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30']) }}>
        @if($placeholder)
            <option value="" disabled>{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>

    @if($error)
        <p class="mt-1 text-xs text-red-400" role="alert">{{ $error }}</p>
    @endif

    @error($attributes->get('wire:model', $attributes->get('name', '')))
        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
    @enderror
</div>
