<?php

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public Transaction $transaction;
    public string $name = '';
    public float|string $amount = '';
    public string $type = 'expense';
    public string $currency = 'RSD';
    public string $date = '';
    public ?string $category = null;

    public function mount(Transaction $transaction): void
    {
        // Ensure user owns this transaction
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        $this->transaction = $transaction;
        $this->name = $transaction->name;
        $this->amount = (float) $transaction->amount;
        $this->type = $transaction->type;
        $this->currency = $transaction->currency;
        $this->date = $transaction->date->format('Y-m-d');
        $this->category = $transaction->category;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:income,expense'],
            'currency' => ['required', 'in:RSD,EUR,USD'],
            'date' => ['required', 'date'],
            'category' => $this->type === 'expense' 
                ? ['required', 'in:bills,food,rest'] 
                : ['nullable', 'in:bills,food,rest'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Transaction name is required.',
            'name.min' => 'Name must be at least 2 characters.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than 0.',
            'category.required' => 'Category is required for expenses.',
        ];
    }

    public function updatedType(): void
    {
        if ($this->type === 'income') {
            $this->category = null;
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->transaction->update([
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'currency' => $validated['currency'],
            'date' => $validated['date'],
            'category' => $validated['category'],
        ]);

        session()->flash('message', 'Transaction updated successfully!');
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function delete(): void
    {
        $this->transaction->delete();
        session()->flash('message', 'Transaction deleted successfully!');
        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<section class="w-full page-enter">
    <div class="mx-auto w-full max-w-xl sm:px-0">
        {{-- Header --}}
        <div class="pb-3 sm:pb-5">
            <h1 class="text-xl font-semibold text-white">Edit Transaction</h1>
            <p class="mt-0.5 text-sm text-zinc-500">Update or delete this transaction</p>
        </div>

        <form wire:submit="save" class="card space-y-4 sm:space-y-5">
            @if (session('message'))
                <div class="toast-success">{{ session('message') }}</div>
            @endif

            {{-- Name and Type --}}
            <div class="flex gap-3 sm:gap-4">
                <div class="w-2/3">
                    <flux:input 
                        wire:model="name" 
                        :label="__('Name')" 
                        placeholder="Transaction name"
                    />
                    @error('name')
                        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="w-1/3">
                    <flux:select wire:model.live="type" :label="__('Type')">
                        <flux:select.option value="expense">Expense</flux:select.option>
                        <flux:select.option value="income">Income</flux:select.option>
                    </flux:select>
                    @error('type')
                        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Category (only for expenses) --}}
            @if ($type === 'expense')
                <div>
                    <flux:select wire:model="category" :label="__('Category')">
                        <flux:select.option value="">Select category</flux:select.option>
                        @foreach (Transaction::CATEGORY_LABELS as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('category')
                        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- Amount and Currency --}}
            <div class="flex gap-3 sm:gap-4">
                <div class="w-2/3">
                    <flux:input 
                        wire:model="amount" 
                        :label="__('Amount')" 
                        type="text"
                        placeholder="0.00"
                        x-mask:dynamic="$money($input, '.', '')"
                    />
                    @error('amount')
                        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="w-1/3">
                    <flux:select wire:model="currency" :label="__('Currency')">
                        @foreach (Transaction::CURRENCIES as $curr)
                            <flux:select.option value="{{ $curr }}">{{ $curr }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('currency')
                        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Date --}}
            <div>
                <flux:date-picker 
                    wire:model="date"
                    :label="__('Date')"
                    placeholder="Select date"
                />
                @error('date')
                    <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-2">
                <flux:button type="submit" variant="primary" class="btn-press flex-1" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save Changes</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
                <flux:button 
                    type="button" 
                    variant="danger" 
                    wire:click="delete" 
                    wire:confirm="Are you sure you want to delete this transaction?"
                    class="btn-press"
                >
                    Delete
                </flux:button>
            </div>
        </form>
    </div>
</section>
