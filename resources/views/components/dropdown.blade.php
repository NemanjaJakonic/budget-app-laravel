@props([
    'position' => 'bottom',
    'align' => 'start',
    'width' => 'w-48',
])

<div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block">
    <div @click="open = !open" class="cursor-pointer">
        {{ $trigger ?? $slot }}
    </div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @class([
            'absolute z-50 min-w-48 rounded-xl border border-zinc-700/50 bg-zinc-800 py-1 shadow-xl',
            $width,
            'top-full mt-1' => $position === 'bottom',
            'bottom-full mb-1' => $position === 'top',
            'left-0' => $align === 'start',
            'right-0' => $align === 'end',
        ])
        style="display: none;"
    >
        {{ $content ?? '' }}
    </div>
</div>
