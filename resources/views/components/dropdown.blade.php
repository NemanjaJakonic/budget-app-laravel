@props([
    'position' => 'bottom',
    'align' => 'start',
    'width' => 'w-48',
])

<div
    x-data="{
        open: false,
        triggerEl: null,
        panelEl: null,
        positionPanel() {
            if (!this.triggerEl || !this.panelEl) return;
            const rect = this.triggerEl.getBoundingClientRect();
            const panel = this.panelEl;
            const gap = 4;

            if ('{{ $position }}' === 'bottom') {
                panel.style.top = (rect.bottom + gap) + 'px';
            } else {
                panel.style.top = 'auto';
                panel.style.bottom = (window.innerHeight - rect.top + gap) + 'px';
            }

            if ('{{ $align }}' === 'end') {
                panel.style.right = (window.innerWidth - rect.left + gap) + 'px';
                panel.style.left = 'auto';
            } else {
                panel.style.left = rect.right + gap + 'px';
                panel.style.right = 'auto';
            }
        }
    }"
    @click.outside="open = false"
    @resize.window="open && positionPanel()"
    class="relative inline-block text-left"
>
    <div
        @click="open = !open; $nextTick(() => { triggerEl = $el; positionPanel() })"
        x-ref="trigger"
        class="cursor-pointer"
    >
        {{ $trigger ?? $slot }}
    </div>

    <div
        x-ref="panel"
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @before-show="panelEl = $el; positionPanel()"
        class="fixed z-[100] min-w-48 rounded-xl border border-zinc-700/50 bg-zinc-800 py-1 shadow-xl {{ $width }}"
    >
        {{ $content ?? '' }}
    </div>
</div>
