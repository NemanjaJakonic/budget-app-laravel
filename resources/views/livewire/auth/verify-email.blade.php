<x-layouts.auth>
    <div class="mt-4 flex flex-col gap-6">
        <p class="text-sm text-zinc-400 text-center">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <p class="text-sm text-center font-medium text-green-600 dark:text-green-400">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-form.button type="submit" variant="primary" class="w-full">
                    {{ __('Resend verification email') }}
                </x-form.button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-form.button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Log out') }}
                </x-form.button>
            </form>
        </div>
    </div>
</x-layouts.auth>
