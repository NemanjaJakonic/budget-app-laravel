@props([
    'variant' => 'segmented',
    'selected' => null,
])

<div
    x-data="{ selected: @js($selected) }"
    {{ $attributes->merge(['class' => 'inline-flex rounded-lg border border-zinc-700/50 bg-zinc-800/60 p-0.5']) }}
>
    {{ $slot }}
</div>
