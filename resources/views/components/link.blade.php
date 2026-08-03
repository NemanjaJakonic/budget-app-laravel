@props([
    'href' => null,
    'variant' => 'default',
])

@php
    $baseClasses = 'text-sm font-medium transition-colors';
    $variantClasses = match($variant) {
        'primary' => 'text-accent hover:text-accent/80',
        default => 'text-zinc-400 hover:text-white',
    };
    $classes = "$baseClasses $variantClasses " . ($attributes->get('class', ''));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
