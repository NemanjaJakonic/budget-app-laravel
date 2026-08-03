@props([
    'icon' => null,
    'href' => null,
    'danger' => false,
    'as' => 'button',
])

@php
    $baseClasses = 'flex w-full items-center gap-2.5 px-3 py-2 text-sm transition-colors';
    $colorClasses = $danger ? 'text-red-400 hover:bg-red-500/10' : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white';
    $classes = "$baseClasses $colorClasses " . ($attributes->get('class', ''));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-icon :name="$icon" class="size-4 shrink-0" />@endif
        {{ $slot }}
    </a>
@else
    <button type="button" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-icon :name="$icon" class="size-4 shrink-0" />@endif
        {{ $slot }}
    </button>
@endif
