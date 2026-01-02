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

<section class="w-full">
    <div class="mx-auto w-full max-w-xl p-4">
        <h1 class="pb-4 text-center text-lg font-bold text-white">Budget Profile</h1>

        <form wire:submit="save" class="rounded-xl bg-zinc-800/40 p-6 space-y-6">
            @if (session('message'))
                <div class="rounded bg-emerald-500/20 p-3 text-sm text-emerald-400">
                    {{ session('message') }}
                </div>
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
                <p class="mt-1 text-xs text-zinc-500">
                    Enter your initial balance in RSD. This is used to calculate your total balance.
                </p>
                @error('starting_balance')
                    <span class="text-xs text-red-400">{{ $message }}</span>
                @enderror
            </div>

            <div class="pt-4">
                <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </form>
    </div>
</section>
