@props([
    'name' => null,
    'variant' => 'default',
])

@php
    $baseClasses = 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium';
    $variantClasses = match($variant) {
        'green', 'success' => 'bg-emerald-500/20 text-emerald-400',
        'red', 'danger' => 'bg-red-500/20 text-red-400',
        'blue', 'info' => 'bg-blue-500/20 text-blue-400',
        'yellow', 'warning' => 'bg-amber-500/20 text-amber-400',
        default => 'bg-zinc-700/60 text-zinc-400',
    };
    $classes = "$baseClasses $variantClasses " . ($attributes->get('class', ''));
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
