<?php

use App\Helpers\CurrencyHelper;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';
    public string $selectedType = '';
    public string $selectedCategory = '';
    public int $selectedMonth = 0;
    public int|string $selectedYear = 0;
    public int $perPage = 15;

    protected $queryString = [
        'selectedMonth' => ['as' => 'month'],
        'selectedYear' => ['as' => 'year'],
        'selectedType' => ['as' => 'type', 'except' => ''],
        'selectedCategory' => ['as' => 'category', 'except' => ''],
        'search' => ['as' => 'q', 'except' => ''],
    ];

    public function mount(): void
    {
        $this->selectedMonth = (int) request()->get('month', now()->month);
        $this->selectedYear = (int) request()->get('year', now()->year);
        $this->selectedType = request()->get('type', '');
        $this->selectedCategory = request()->get('category', '');
        $this->search = request()->get('q', '');
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

    public function updatedSearch(): void
    {
        $this->perPage = 15;
    }

    public function updatedSelectedType(): void
    {
        $this->perPage = 15;
    }

    public function updatedSelectedCategory(): void
    {
        $this->perPage = 15;
    }

    public function updatedSelectedMonth(): void
    {
        $this->perPage = 15;
    }

    public function updatedSelectedYear(): void
    {
        $this->perPage = 15;
    }

    public function loadMore(): void
    {
        $this->perPage += 15;
    }

    public function deleteTransaction(int $id): void
    {
        Transaction::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();
    }

    private function applyFilters($query)
    {
        if ($this->selectedMonth > 0) {
            $query->whereMonth('date', $this->selectedMonth);
        }

        if ($this->selectedYear > 0) {
            $query->whereYear('date', $this->selectedYear);
        }

        if ($this->selectedType !== '') {
            $query->where('type', $this->selectedType);
        }

        if ($this->selectedCategory !== '') {
            $query->where('category', $this->selectedCategory);
        }

        if ($this->search !== '') {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return $query;
    }

    public function getTransactions()
    {
        $query = Auth::user()->transactions()
            ->orderBy('date', 'desc');

        return $this->applyFilters($query)->paginate($this->perPage);
    }

    /**
     * @return array{income: array<string, float>, expense: array<string, float>, net: array<string, float>}
     */
    public function getTotals(): array
    {
        $query = Auth::user()->transactions()
            ->selectRaw('type, currency, SUM(amount) as total')
            ->groupBy('type', 'currency');

        $rows = $this->applyFilters($query)->get();

        $income = [];
        $expense = [];

        foreach ($rows as $row) {
            if ($row->type === 'income') {
                $income[$row->currency] = (float) $row->total;
            } else {
                $expense[$row->currency] = (float) $row->total;
            }
        }

        $currencies = array_unique(array_merge(array_keys($income), array_keys($expense)));
        sort($currencies);

        $net = [];
        foreach ($currencies as $currency) {
            $net[$currency] = ($income[$currency] ?? 0) - ($expense[$currency] ?? 0);
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $net,
        ];
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

        {{-- Search --}}
        <div class="pb-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search transactions..."
                icon="magnifying-glass"
                clearable
            />
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap gap-4 pb-4">
            <div class="min-w-0 flex-1">
                <flux:select wire:model.live="selectedType">
                    <flux:select.option value="">All Types</flux:select.option>
                    @foreach (Transaction::TYPES as $type)
                        <flux:select.option value="{{ $type }}">{{ ucfirst($type) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="min-w-0 flex-1">
                <flux:select wire:model.live="selectedCategory">
                    <flux:select.option value="">All Categories</flux:select.option>
                    @foreach (Transaction::CATEGORY_LABELS as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="min-w-0 flex-1">
                <flux:select wire:model.live="selectedMonth">
                    @foreach ($this->getMonths() as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="min-w-0 flex-1">
                <flux:select wire:model.live="selectedYear">
                    @foreach ($this->getYears() as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        @php($transactions = $this->getTransactions())
        @php($totals = $this->getTotals())

        {{-- Transactions List --}}
        <div class="space-y-3">
            @forelse ($transactions as $transaction)
                <div wire:key="txn-{{ $transaction->id }}" class="flex items-center gap-4 rounded-xl bg-zinc-800/40 py-2">
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
                    No transactions found for the selected filters
                </div>
            @endforelse
        </div>

        {{-- Totals Summary --}}
        @if ($transactions->isNotEmpty())
            <div class="mt-6 border-zinc-700/50 border-t-2 pt-4">
                <h2 class="mb-3 text-sm font-semibold text-zinc-300">Summary</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    {{-- Income --}}
                    <div>
                        <p class="text-xs text-zinc-500">Income</p>
                        @forelse ($totals['income'] as $currency => $amount)
                            <p class="text-sm font-semibold text-emerald-400">
                                {{ CurrencyHelper::format($amount, $currency) }}
                            </p>
                        @empty
                            <p class="text-sm text-zinc-500">-</p>
                        @endforelse
                    </div>

                    {{-- Expenses --}}
                    <div>
                        <p class="text-xs text-zinc-500">Expenses</p>
                        @forelse ($totals['expense'] as $currency => $amount)
                            <p class="text-sm font-semibold text-red-400">
                                -{{ CurrencyHelper::format($amount, $currency) }}
                            </p>
                        @empty
                            <p class="text-sm text-zinc-500">-</p>
                        @endforelse
                    </div>

                    {{-- Net --}}
                    <div>
                        <p class="text-xs text-zinc-500">Net</p>
                        @foreach ($totals['net'] as $currency => $amount)
                            <p class="text-sm font-semibold {{ $amount >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $amount < 0 ? '-' : '' }}{{ CurrencyHelper::format(abs($amount), $currency) }}
                            </p>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Infinite Scroll Sentinel --}}
        @if ($transactions->hasMorePages())
            <div
                wire:key="load-more-{{ $this->perPage }}"
                x-intersect="$wire.loadMore()"
                class="flex justify-center py-6"
            >
                <flux:icon.arrow-path class="size-5 animate-spin text-zinc-400" />
            </div>
        @endif
    </div>
</section>
