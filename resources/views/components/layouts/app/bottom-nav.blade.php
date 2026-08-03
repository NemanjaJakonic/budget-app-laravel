<nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-zinc-700/50 bg-zinc-900/95 backdrop-blur-sm" style="padding-bottom: env(safe-area-inset-bottom, 0);" role="navigation" aria-label="Main navigation">
    <div class="mx-auto flex h-16 max-w-md items-center justify-around lg:max-w-2xl">
        <a
            href="{{ route('dashboard') }}"
            class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'text-emerald-400' : 'text-zinc-400' }}"
            @if(request()->routeIs('dashboard')) data-active @endif
            wire:navigate
            aria-label="Dashboard"
            @if(request()->routeIs('dashboard')) aria-current="page" @endif
        >
            <x-icon name="home" class="size-5" />
            <span class="font-medium">{{ __('Dashboard') }}</span>
        </a>

        <a
            href="{{ route('transactions.index') }}"
            class="bottom-nav-item {{ request()->routeIs('transactions.index') ? 'text-emerald-400' : 'text-zinc-400' }}"
            @if(request()->routeIs('transactions.index')) data-active @endif
            wire:navigate
            aria-label="Transactions"
            @if(request()->routeIs('transactions.index')) aria-current="page" @endif
        >
            <x-icon name="banknotes" class="size-5" />
            <span class="font-medium">{{ __('Transactions') }}</span>
        </a>

        <a
            href="{{ route('transactions.create') }}"
            class="bottom-nav-item {{ request()->routeIs('transactions.create') ? 'text-emerald-400' : 'text-zinc-400' }}"
            @if(request()->routeIs('transactions.create')) data-active @endif
            wire:navigate
            aria-label="Add transaction"
            @if(request()->routeIs('transactions.create')) aria-current="page" @endif
        >
            <x-icon name="plus-circle" class="size-5" />
            <span class="font-medium">{{ __('Add') }}</span>
        </a>

        <a
            href="{{ route('savings') }}"
            class="bottom-nav-item {{ request()->routeIs('savings') ? 'text-emerald-400' : 'text-zinc-400' }}"
            @if(request()->routeIs('savings')) data-active @endif
            wire:navigate
            aria-label="Savings"
            @if(request()->routeIs('savings')) aria-current="page" @endif
        >
            <x-icon name="chart-bar" class="size-5" />
            <span class="font-medium">{{ __('Savings') }}</span>
        </a>

        <a
            href="{{ route('budget-profile') }}"
            class="bottom-nav-item {{ request()->routeIs('budget-profile') ? 'text-emerald-400' : 'text-zinc-400' }}"
            @if(request()->routeIs('budget-profile')) data-active @endif
            wire:navigate
            aria-label="Profile"
            @if(request()->routeIs('budget-profile')) aria-current="page" @endif
        >
            <x-icon name="user" class="size-5" />
            <span class="font-medium">{{ __('Profile') }}</span>
        </a>
    </div>
</nav>
