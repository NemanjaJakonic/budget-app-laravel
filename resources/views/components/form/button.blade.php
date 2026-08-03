@props([
    'variant' => 'default',
    'size' => 'md',
    'icon' => null,
    'iconTrailing' => null,
    'as' => 'button',
    'href' => null,
])

@php
    $baseClasses = 'btn-press inline-flex items-center justify-center gap-2 font-medium transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:opacity-50 disabled:pointer-events-none';

    $variantClasses = match($variant) {
        'primary' => 'bg-accent text-white hover:bg-accent/90 shadow-sm',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 shadow-sm',
        'ghost' => 'text-zinc-400 hover:text-white hover:bg-zinc-700/50',
        'outline' => 'border border-zinc-600/60 text-zinc-300 hover:bg-zinc-700/50',
        default => 'bg-zinc-700/60 text-zinc-300 hover:bg-zinc-600/60',
    };

    $sizeClasses = match($size) {
        'xs' => 'rounded px-2 py-1 text-xs',
        'sm' => 'rounded-lg px-3 py-1.5 text-sm',
        'lg' => 'rounded-lg px-5 py-2.5 text-base',
        default => 'rounded-lg px-4 py-2 text-sm',
    };

    $classes = "$baseClasses $variantClasses $sizeClasses " . ($attributes->get('class', ''));
    $tag = $href ? 'a' : $as;
@endphp

@if($tag === 'a')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-icon :name="$icon" class="size-4 shrink-0" />@endif
        {{ $slot }}
        @if($iconTrailing)<x-icon :name="$iconTrailing" class="size-4 shrink-0" />@endif
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-icon :name="$icon" class="size-4 shrink-0" />@endif
        {{ $slot }}
        @if($iconTrailing)<x-icon :name="$iconTrailing" class="size-4 shrink-0" />@endif
    </button>
@endif
