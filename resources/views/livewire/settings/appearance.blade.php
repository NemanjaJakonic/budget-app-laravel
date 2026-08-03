<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div x-data variant="segmented" class="flex gap-1 rounded-lg border border-zinc-600/60 p-1">
            <button
                type="button"
                @click="$flux.appearance = 'light'"
                :class="$flux.appearance === 'light' ? 'bg-zinc-700/60 text-white' : 'text-zinc-400 hover:text-white'"
                class="flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
            >
                <x-icon name="sun" class="size-4" />
                {{ __('Light') }}
            </button>
            <button
                type="button"
                @click="$flux.appearance = 'dark'"
                :class="$flux.appearance === 'dark' ? 'bg-zinc-700/60 text-white' : 'text-zinc-400 hover:text-white'"
                class="flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
            >
                <x-icon name="moon" class="size-4" />
                {{ __('Dark') }}
            </button>
            <button
                type="button"
                @click="$flux.appearance = 'system'"
                :class="$flux.appearance === 'system' ? 'bg-zinc-700/60 text-white' : 'text-zinc-400 hover:text-white'"
                class="flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
            >
                <x-icon name="computer-desktop" class="size-4" />
                {{ __('System') }}
            </button>
        </div>
    </x-settings.layout>
</section>
