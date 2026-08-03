@props([
    'name' => null,
    'initials' => null,
    'size' => 'md',
])

@php
    $sizeClasses = match($size) {
        'sm' => 'size-7 text-xs',
        'lg' => 'size-10 text-sm',
        default => 'size-8 text-xs',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <span class="flex shrink-0 items-center justify-center rounded-lg bg-zinc-700 font-semibold text-white {{ $sizeClasses }}">
        {{ $initials ?? ($name ? strtoupper(substr($name, 0, 2)) : '?') }}
    </span>
    @if($name && $size !== 'sm')
        <span class="text-sm font-medium text-white">{{ $name }}</span>
    @endif
</div>
