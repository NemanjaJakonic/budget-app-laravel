<nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-zinc-200 bg-zinc-50/95 backdrop-blur-sm dark:border-zinc-700 dark:bg-zinc-900/95" style="padding-bottom: env(safe-area-inset-bottom, 0);">
    <div class="mx-auto flex h-16 max-w-md items-center justify-around lg:max-w-2xl">
        <a
            href="{{ route('dashboard') }}"
            class="flex flex-1 flex-col items-center justify-center gap-1 px-2 py-2 text-xs transition-colors hover:text-emerald-500 dark:hover:text-emerald-400 lg:flex-initial lg:px-4 {{ request()->routeIs('dashboard') ? 'text-emerald-500 dark:text-emerald-400' : 'text-zinc-600 dark:text-zinc-400' }}"
            wire:navigate
        >
            <flux:icon.home class="size-6" />
            <span class="font-medium">{{ __('Dashboard') }}</span>
        </a>

        <a
            href="{{ route('transactions.index') }}"
            class="flex flex-1 flex-col items-center justify-center gap-1 px-2 py-2 text-xs transition-colors hover:text-emerald-500 dark:hover:text-emerald-400 lg:flex-initial lg:px-4 {{ request()->routeIs('transactions.index') ? 'text-emerald-500 dark:text-emerald-400' : 'text-zinc-600 dark:text-zinc-400' }}"
            wire:navigate
        >
            <flux:icon.banknotes class="size-6" />
            <span class="font-medium">{{ __('Transactions') }}</span>
        </a>

        <a
            href="{{ route('transactions.create') }}"
            class="flex flex-1 flex-col items-center justify-center gap-1 px-2 py-2 text-xs transition-colors hover:text-emerald-500 dark:hover:text-emerald-400 lg:flex-initial lg:px-4 {{ request()->routeIs('transactions.create') ? 'text-emerald-500 dark:text-emerald-400' : 'text-zinc-600 dark:text-zinc-400' }}"
            wire:navigate
        >
            <flux:icon.plus-circle class="size-6" />
            <span class="font-medium">{{ __('Add') }}</span>
        </a>

        <a
            href="{{ route('savings') }}"
            class="flex flex-1 flex-col items-center justify-center gap-1 px-2 py-2 text-xs transition-colors hover:text-emerald-500 dark:hover:text-emerald-400 lg:flex-initial lg:px-4 {{ request()->routeIs('savings') ? 'text-emerald-500 dark:text-emerald-400' : 'text-zinc-600 dark:text-zinc-400' }}"
            wire:navigate
        >
            <flux:icon.chart-bar class="size-6" />
            <span class="font-medium">{{ __('Savings') }}</span>
        </a>

        <a
            href="{{ route('budget-profile') }}"
            class="flex flex-1 flex-col items-center justify-center gap-1 px-2 py-2 text-xs transition-colors hover:text-emerald-500 dark:hover:text-emerald-400 lg:flex-initial lg:px-4 {{ request()->routeIs('budget-profile') ? 'text-emerald-500 dark:text-emerald-400' : 'text-zinc-600 dark:text-zinc-400' }}"
            wire:navigate
        >
            <flux:icon.user class="size-6" />
            <span class="font-medium">{{ __('Profile') }}</span>
        </a>
    </div>
</nav>
