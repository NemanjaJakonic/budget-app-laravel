@props([
    'position' => 'bottom',
    'align' => 'start',
    'width' => 'w-48',
])

<div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block text-left">
    <div @click="open = !open" class="cursor-pointer">
        {{ $trigger ?? $slot }}
    </div>

    <div
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @class([
            'fixed z-[100] min-w-48 rounded-xl border border-zinc-700/50 bg-zinc-800 py-1 shadow-xl',
            $width,
        ])
        :style="{
            top: '{{ $position }}' === 'bottom' ? ($el.previousElementSibling.getBoundingClientRect().bottom + 4) + 'px' : 'auto',
            bottom: '{{ $position }}' === 'top' ? (window.innerHeight - $el.previousElementSibling.getBoundingClientRect().top + 4) + 'px' : 'auto',
            left: '{{ $align }}' === 'start' ? $el.previousElementSibling.getBoundingClientRect().left + 'px' : 'auto',
            right: '{{ $align }}' === 'end' ? (window.innerWidth - $el.previousElementSibling.getBoundingClientRect().right) + 'px' : 'auto',
        }"
    >
        {{ $content ?? '' }}
    </div>
</div>
