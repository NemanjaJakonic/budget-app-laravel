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

<section class="w-full">
    <div class="mx-auto w-full max-w-xl">
        <h1 class="pb-4 text-center text-lg font-bold text-white">Edit Transaction</h1>

        <form wire:submit="save" class="rounded-xl bg-zinc-800/40 space-y-6">
            @if (session('message'))
                <div class="rounded bg-emerald-500/20 p-3 text-sm text-emerald-400">
                    {{ session('message') }}
                </div>
            @endif

            {{-- Name and Type --}}
            <div class="flex gap-4">
                <div class="w-2/3">
                    <flux:input 
                        wire:model="name" 
                        :label="__('Name')" 
                        placeholder="Transaction name"
                    />
                    @error('name')
                        <span class="text-xs text-red-400">{{ $message }}</span>
                    @enderror
                </div>
                <div class="w-1/3">
                    <flux:select wire:model.live="type" :label="__('Type')">
                        <flux:select.option value="expense">Expense</flux:select.option>
                        <flux:select.option value="income">Income</flux:select.option>
                    </flux:select>
                    @error('type')
                        <span class="text-xs text-red-400">{{ $message }}</span>
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
                        <span class="text-xs text-red-400">{{ $message }}</span>
                    @enderror
                </div>
            @endif

            {{-- Amount and Currency --}}
            <div class="flex gap-4">
                <div class="w-2/3">
                    <flux:input 
                        wire:model="amount" 
                        :label="__('Amount')" 
                        type="text"
                        placeholder="0.00"
                        x-mask:dynamic="$money($input, '.', '')"
                    />
                    @error('amount')
                        <span class="text-xs text-red-400">{{ $message }}</span>
                    @enderror
                </div>
                <div class="w-1/3">
                    <flux:select wire:model="currency" :label="__('Currency')">
                        @foreach (Transaction::CURRENCIES as $curr)
                            <flux:select.option value="{{ $curr }}">{{ $curr }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('currency')
                        <span class="text-xs text-red-400">{{ $message }}</span>
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
                    <span class="text-xs text-red-400">{{ $message }}</span>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex gap-4 pt-4">
                <flux:button type="submit" variant="primary" class="flex-1" wire:loading.attr="disabled">
                    <span wire:loading.remove>Update</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
                <flux:button 
                    type="button" 
                    variant="danger" 
                    wire:click="delete" 
                    wire:confirm="Are you sure you want to delete this transaction?"
                >
                    Delete
                </flux:button>
            </div>
        </form>
    </div>
</section>
