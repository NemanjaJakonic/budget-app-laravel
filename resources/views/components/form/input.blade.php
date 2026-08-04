@props([
    'label' => null,
    'type' => 'text',
    'icon' => null,
    'viewable' => false,
    'clearable' => false,
    'error' => null,
])

@php
    $baseClasses = 'w-full rounded-lg border border-zinc-600/60 bg-zinc-800/80 px-3 py-2 text-sm text-white placeholder-zinc-500 transition-all focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30';
    $hasIcon = $icon || isset($icon);
@endphp

<div>
    @if($label)
        <label class="mb-1.5 block text-sm font-medium text-zinc-300">{{ $label }}</label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-icon :name="$icon" class="size-4 text-zinc-500" />
            </div>
        @endif

        @if($viewable)
            <div x-data="{ show: false }" class="relative">
                <input
                    {{ $attributes->merge([
                        'type' => 'password',
                        'class' => $baseClasses . ($icon ? ' pl-9' : '') . ' pr-9',
                    ]) }}
                    :type="show ? 'text' : 'password'"
                />
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-500 hover:text-zinc-300">
                    <x-icon name="eye" class="size-4" x-show="!show" />
                    <x-icon name="eye-slash" class="size-4" x-show="show" />
                </button>
            </div>
        @elseif($clearable)
            @php
                $wireModel = $attributes->wire('model')->value();
                $wireProp = str($wireModel)->before('.')->toString();
            @endphp
            <div x-data="{ value: @entangle($attributes->wire('model')) }" class="relative">
                <input
                    {{ $attributes->merge([
                        'type' => $type,
                        'class' => $baseClasses . ($icon ? ' pl-9' : '') . ' pr-9',
                    ]) }}
                    x-model="value"
                />
                <button type="button" x-show="value" @click="$wire.set('{{ $wireProp }}', ''); value = ''" class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-500 hover:text-zinc-300">
                    <x-icon name="x-mark" class="size-4" />
                </button>
            </div>
        @else
            <input
                {{ $attributes->merge([
                    'type' => $type,
                    'class' => $baseClasses . ($icon ? ' pl-9' : ''),
                ]) }}
            />
        @endif
    </div>

    @if($error)
        <p class="mt-1 text-xs text-red-400" role="alert">{{ $error }}</p>
    @endif

    @error($attributes->get('wire:model', $attributes->get('name', '')))
        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
    @enderror
</div>
