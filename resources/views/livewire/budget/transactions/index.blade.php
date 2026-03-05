<?php

use App\Helpers\CurrencyHelper;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public int $selectedMonth = 0;
    public int|string $selectedYear = 0;
    public int $perPage = 6;

    protected $queryString = [
        'selectedMonth' => ['as' => 'month'],
        'selectedYear' => ['as' => 'year'],
    ];

    public function mount(): void
    {
        $this->selectedMonth = request()->get('month', now()->month);
        $this->selectedYear = request()->get('year', now()->year);
    }

    public function getMonths(): array
    {
        return [
            0 => 'All Months',
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    }

    public function getYears(): array
    {
        $currentYear = now()->year;
        return [
            0 => 'All Years',
            $currentYear => (string) $currentYear,
            $currentYear - 1 => (string) ($currentYear - 1),
            $currentYear - 2 => (string) ($currentYear - 2),
        ];
    }

    public function updatedSelectedMonth(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedYear(): void
    {
        $this->resetPage();
    }

    public function deleteTransaction(int $id): void
    {
        Transaction::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();
    }

    public function getTransactions()
    {
        $query = Auth::user()->transactions()
            ->orderBy('date', 'desc');

        if ($this->selectedMonth > 0) {
            $query->whereMonth('date', $this->selectedMonth);
        }

        if ($this->selectedYear > 0) {
            $query->whereYear('date', $this->selectedYear);
        }

        return $query->paginate($this->perPage);
    }

    public function exportToExcel()
    {
        return redirect()->route('api.export-transactions');
    }
}; ?>

<section class="w-full">
    <div class="mx-auto w-full max-w-4xl py-4">
        <div class="flex items-center justify-between pb-4">
            <h1 class="text-lg font-bold text-white">All Transactions</h1>
            <a href="{{ route('api.export-transactions') }}" class="flex items-center gap-2 rounded bg-emerald-500 px-4 py-2 text-sm text-white transition-colors hover:bg-emerald-600">
                <flux:icon.arrow-down-tray class="size-5" />
                Export to Excel
            </a>
        </div>

        {{-- Filters --}}
        <div class="flex gap-4 pb-4">
            <flux:select wire:model.live="selectedMonth" class="w-full">
                @foreach ($this->getMonths() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="selectedYear" class="w-full">
                @foreach ($this->getYears() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Transactions List --}}
        <div class="space-y-3" wire:transition>
            @forelse ($this->getTransactions() as $transaction)
                <div class="flex items-center gap-4 rounded-xl bg-zinc-800/40 py-2">
                    <div class="flex-1">
                        <a href="{{ route('transactions.edit', $transaction->id) }}" class="text-sm text-white hover:text-emerald-400" wire:navigate>
                            {{ $transaction->name }}
                        </a>
                        <p class="text-xs text-zinc-400">
                            {{ $transaction->date->format('l, j M Y') }}
                            @if ($transaction->category_label)
                                <span class="ml-2 rounded bg-emerald-500/20 px-1.5 py-0.5 text-xs text-emerald-400">
                                    {{ $transaction->category_label }}
                                </span>
                            @endif
                        </p>
                    </div>
                    <span class="flex-none text-right text-sm font-semibold {{ $transaction->type === 'expense' ? 'text-red-400' : 'text-emerald-400' }}">
                        {{ $transaction->type === 'expense' ? '-' : '' }}{{ CurrencyHelper::format($transaction->amount, $transaction->currency) }}
                    </span>
                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item :href="route('transactions.edit', $transaction->id)" icon="pencil" wire:navigate>
                                {{ __('Edit') }}
                            </flux:menu.item>
                            <flux:menu.item 
                                wire:click="deleteTransaction({{ $transaction->id }})" 
                                wire:confirm="Are you sure you want to delete this transaction?"
                                icon="trash" 
                                class="text-red-400"
                            >
                                {{ __('Delete') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            @empty
                <div class="py-8 text-center text-zinc-500">
                    No transactions found for the selected period
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $this->getTransactions()->links() }}
        </div>
    </div>
</section>
