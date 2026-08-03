<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <aside class="hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700/50 dark:bg-zinc-900 sticky top-0 h-screen overflow-y-auto" id="sidebar">
            <div class="flex flex-col h-full p-4">
                <div class="flex items-center justify-between mb-4">
                    <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                        <x-app-logo />
                    </a>
                    <button @click="document.getElementById('sidebar').classList.toggle('hidden')" class="lg:hidden p-1 rounded-md hover:bg-zinc-200 dark:hover:bg-zinc-700">
                        <x-icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <nav>
                    <h3 class="mb-2 text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ __('Budget') }}</h3>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('dashboard') }}" wire:navigate @class([
                                'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors',
                                'bg-zinc-200/50 text-zinc-900 dark:bg-zinc-700/50 dark:text-white' => request()->routeIs('dashboard'),
                                'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' => !request()->routeIs('dashboard'),
                            ])>
                                <x-icon name="home" class="size-4 shrink-0" />
                                {{ __('Dashboard') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('transactions.create') }}" wire:navigate @class([
                                'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors',
                                'bg-zinc-200/50 text-zinc-900 dark:bg-zinc-700/50 dark:text-white' => request()->routeIs('transactions.create'),
                                'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' => !request()->routeIs('transactions.create'),
                            ])>
                                <x-icon name="plus-circle" class="size-4 shrink-0" />
                                {{ __('Add Transaction') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('transactions.index') }}" wire:navigate @class([
                                'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors',
                                'bg-zinc-200/50 text-zinc-900 dark:bg-zinc-700/50 dark:text-white' => request()->routeIs('transactions.index'),
                                'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' => !request()->routeIs('transactions.index'),
                            ])>
                                <x-icon name="banknotes" class="size-4 shrink-0" />
                                {{ __('Transactions') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('savings') }}" wire:navigate @class([
                                'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors',
                                'bg-zinc-200/50 text-zinc-900 dark:bg-zinc-700/50 dark:text-white' => request()->routeIs('savings'),
                                'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' => !request()->routeIs('savings'),
                            ])>
                                <x-icon name="chart-bar" class="size-4 shrink-0" />
                                {{ __('Savings') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('budget-profile') }}" wire:navigate @class([
                                'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors',
                                'bg-zinc-200/50 text-zinc-900 dark:bg-zinc-700/50 dark:text-white' => request()->routeIs('budget-profile'),
                                'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' => !request()->routeIs('budget-profile'),
                            ])>
                                <x-icon name="user" class="size-4 shrink-0" />
                                {{ __('Profile') }}
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="flex-1"></div>

                <nav class="mb-4">
                    <ul class="space-y-1">
                        <li>
                            <a href="https://github.com/laravel/livewire-starter-kit" target="_blank" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white">
                                <x-icon name="folder-git-2" class="size-4 shrink-0" />
                                {{ __('Repository') }}
                            </a>
                        </li>
                        <li>
                            <a href="https://laravel.com/docs/starter-kits#livewire" target="_blank" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white">
                                <x-icon name="book-open-text" class="size-4 shrink-0" />
                                {{ __('Documentation') }}
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Desktop User Menu -->
                <div class="hidden lg:block">
                    <x-dropdown position="bottom" align="start">
                        <x-slot name="trigger">
                            <x-profile
                                :name="auth()->user()->name"
                                :initials="auth()->user()->initials()"
                                data-test="sidebar-menu-button"
                            />
                        </x-slot>

                        <x-slot name="content">
                            <div class="w-[220px]">
                                <div class="p-0 text-sm font-normal">
                                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                        <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                            <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                                {{ auth()->user()->initials() }}
                                            </span>
                                        </span>
                                        <div class="grid flex-1 text-start text-sm leading-tight">
                                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                            <span class="truncate text-xs text-zinc-400">{{ auth()->user()->email }}</span>
                                        </div>
                                    </div>
                                </div>

                                <x-separator />

                                <div class="py-1">
                                    <x-menu-item icon="cog" :href="route('profile.edit')" wire:navigate>{{ __('Settings') }}</x-menu-item>
                                </div>

                                <x-separator />

                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <x-menu-item icon="arrow-right-start-on-rectangle" type="submit" class="w-full" data-test="logout-button">
                                        {{ __('Log Out') }}
                                    </x-menu-item>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </aside>

        <!-- Header -->
        <header class="!px-2 lg:!px-2">
            <div class="mx-auto w-full max-w-4xl flex items-center">
                <a href="{{ route('dashboard') }}" class="ms-2 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                    <x-app-logo />
                </a>

                <div class="flex-1"></div>

                <x-dropdown position="top" align="end">
                    <x-slot name="trigger">
                        <x-profile
                            :initials="auth()->user()->initials()"
                        />
                    </x-slot>

                    <x-slot name="content">
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs text-zinc-400">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>

                        <x-separator />

                        <div class="py-1">
                            <x-menu-item icon="cog" :href="route('profile.edit')" wire:navigate>{{ __('Settings') }}</x-menu-item>
                        </div>

                        <x-separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <x-menu-item icon="arrow-right-start-on-rectangle" type="submit" class="w-full" data-test="logout-button">
                                {{ __('Log Out') }}
                            </x-menu-item>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </header>

        <main class="!px-2 !pt-6 !pb-16 lg:!px-2 lg:!py-8">
            {{ $slot }}
        </main>

        <x-layouts.app.bottom-nav />

        @stack('scripts')
    </body>
</html>
