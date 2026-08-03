@props([
    'variant' => 'info',
    'icon' => null,
])

@php
    $baseClasses = 'flex items-start gap-3 rounded-lg border p-3 sm:p-4';
    $variantClasses = match($variant) {
        'success' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400',
        'danger', 'error' => 'border-red-500/30 bg-red-500/10 text-red-400',
        'warning' => 'border-amber-500/30 bg-amber-500/10 text-amber-400',
        default => 'border-blue-500/30 bg-blue-500/10 text-blue-400',
    };
    $classes = "$baseClasses $variantClasses " . ($attributes->get('class', ''));
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <x-icon :name="$icon" class="mt-0.5 size-4 shrink-0" />
    @endif
    <div class="text-sm">{{ $slot }}</div>
</div>
