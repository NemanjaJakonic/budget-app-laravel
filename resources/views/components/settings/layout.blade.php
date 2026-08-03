<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <nav>
            <ul class="space-y-1">
                <li>
                    <a
                        href="{{ route('profile.edit') }}"
                        wire:navigate
                        class="block rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('profile.edit') ? 'bg-zinc-700/50 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-700/30' }}"
                    >
                        {{ __('Profile') }}
                    </a>
                </li>
                <li>
                    <a
                        href="{{ route('user-password.edit') }}"
                        wire:navigate
                        class="block rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('user-password.edit') ? 'bg-zinc-700/50 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-700/30' }}"
                    >
                        {{ __('Password') }}
                    </a>
                </li>
                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    <li>
                        <a
                            href="{{ route('two-factor.show') }}"
                            wire:navigate
                            class="block rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('two-factor.show') ? 'bg-zinc-700/50 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-700/30' }}"
                        >
                            {{ __('Two-Factor Auth') }}
                        </a>
                    </li>
                @endif
                <li>
                    <a
                        href="{{ route('appearance.edit') }}"
                        wire:navigate
                        class="block rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('appearance.edit') ? 'bg-zinc-700/50 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-700/30' }}"
                    >
                        {{ __('Appearance') }}
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <x-separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <x-heading>{{ $heading ?? '' }}</x-heading>
        <x-subheading>{{ $subheading ?? '' }}</x-subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
