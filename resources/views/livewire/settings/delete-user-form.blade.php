<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <x-heading>{{ __('Delete account') }}</x-heading>
        <x-subheading>{{ __('Delete your account and all of its resources') }}</x-subheading>
    </div>

    <x-form.button variant="danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" data-test="delete-user-button">
        {{ __('Delete account') }}
    </x-form.button>

    <x-modal name="confirm-user-deletion" maxWidth="lg" :show="$errors->isNotEmpty()" focusable>
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <x-heading as="h3">{{ __('Are you sure you want to delete your account?') }}</x-heading>

                <x-subheading>
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </x-subheading>
            </div>

            <x-form.input wire:model="password" :label="__('Password')" type="password" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <x-form.button variant="default" @click="$dispatch('close-modal')">
                    {{ __('Cancel') }}
                </x-form.button>

                <x-form.button variant="danger" type="submit" data-test="confirm-delete-user-button">
                    {{ __('Delete account') }}
                </x-form.button>
            </div>
        </form>
    </x-modal>
</section>
