<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public float|string $starting_balance = 0;

    public function mount(): void
    {
        $profile = Auth::user()->getOrCreateProfile();
        $this->starting_balance = (float) $profile->starting_balance;
    }

    public function rules(): array
    {
        return [
            'starting_balance' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'starting_balance.required' => 'Please enter a starting balance.',
            'starting_balance.numeric' => 'Please enter a valid number.',
            'starting_balance.min' => 'Starting balance cannot be negative.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $profile = Auth::user()->getOrCreateProfile();
        $profile->update(['starting_balance' => $validated['starting_balance']]);

        session()->flash('message', 'Profile updated successfully!');
        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<section class="w-full page-enter">
    <div class="mx-auto w-full max-w-xl px-4 sm:px-0">
        {{-- Header --}}
        <div class="pb-5">
            <h1 class="text-xl font-semibold text-white">Budget Profile</h1>
            <p class="mt-0.5 text-sm text-zinc-500">Set your starting balance for calculations</p>
        </div>

        <form wire:submit="save" class="card space-y-5">
            @if (session('message'))
                <div class="toast-success">{{ session('message') }}</div>
            @endif

            <div>
                <flux:input 
                    wire:model="starting_balance" 
                    :label="__('Starting Balance (RSD)')" 
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                />
                <p class="mt-2 text-xs text-zinc-500">
                    Enter your initial balance in RSD. This is used to calculate your total balance across all transactions.
                </p>
                @error('starting_balance')
                    <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2">
                <flux:button type="submit" variant="primary" class="btn-press w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save Balance</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </form>
    </div>
</section>
