<?php

use App\Helpers\CurrencyHelper;
use App\Models\Transaction;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';
    public string $selectedType = '';
    public string $selectedCategory = '';
    public int $selectedMonth = 0;
    public int|string $selectedYear = 0;
    public int $perPage = 15;

    /** @var array{RSD: float, EUR: float, USD: float} */
    public array $rates = [];

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
        $this->rates = app(ExchangeRateService::class)->getRates();
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
     * @return array{incomeRsd: float, expenseRsd: float, netRsd: float}
     */
    public function getTotals(): array
    {
        $query = Auth::user()->transactions()
            ->selectRaw('type, currency, SUM(amount) as total')
            ->groupBy('type', 'currency');

        $rows = $this->applyFilters($query)->get();

        $incomeRsd = 0.0;
        $expenseRsd = 0.0;

        foreach ($rows as $row) {
            $partial = new Transaction([
                'amount' => $row->total,
                'currency' => $row->currency,
            ]);
            $amountInRsd = $partial->getAmountInRsd($this->rates);
            if ($row->type === 'income') {
                $incomeRsd += $amountInRsd;
            } else {
                $expenseRsd += $amountInRsd;
            }
        }

        return [
            'incomeRsd' => $incomeRsd,
            'expenseRsd' => $expenseRsd,
            'netRsd' => $incomeRsd - $expenseRsd,
        ];
    }

    public function exportToExcel()
    {
        return redirect()->route('api.export-transactions');
    }
}; ?>

<section class="w-full page-enter">
    <div class="mx-auto w-full max-w-4xl px-3 py-3 sm:px-0 sm:py-4">
        {{-- Header --}}
        <div class="flex items-center justify-between pb-3 sm:pb-4">
            <div>
                <h1 class="text-xl font-semibold text-white">Transactions</h1>
                <p class="mt-0.5 text-sm text-zinc-500">Manage your income and expenses</p>
            </div>
            <flux:button href="{{ route('api.export-transactions') }}" variant="ghost" size="sm" icon="arrow-down-tray" class="btn-press">
                Export
            </flux:button>
        </div>

        @php($transactions = $this->getTransactions())
        @php($totals = $this->getTotals())

        {{-- Search, filters & summary (sticky while scrolling the list) --}}
        <div class="sticky top-0 z-20 -mx-1 border-b border-zinc-700/50 bg-zinc-800/95 px-1 pb-3 backdrop-blur-sm">
            {{-- Search --}}
            <div class="pb-3">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search transactions..."
                    icon="magnifying-glass"
                    clearable
                />
            </div>

            {{-- Filters --}}
            <div class="flex flex-wrap gap-2 pb-2.5 sm:gap-3 sm:pb-3">
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

            {{-- Totals Summary --}}
            @if ($transactions->isNotEmpty())
                <div class="flex items-center justify-between border-t border-zinc-700/50 pt-3">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">Net (RSD)</p>
                    <p class="text-sm font-semibold tabular-nums {{ $totals['netRsd'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ $totals['netRsd'] < 0 ? '−' : '' }}{{ CurrencyHelper::toRSD(abs($totals['netRsd'])) }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Transactions List --}}
        @if ($transactions->isNotEmpty())
            <div class="space-y-1 pt-3">
                @foreach ($transactions as $transaction)
                    <div wire:key="txn-{{ $transaction->id }}" class="list-item-enter group flex items-center gap-3 rounded-lg px-2.5 py-2 transition-colors hover:bg-zinc-700/40 sm:gap-4 sm:px-3 sm:py-2.5" style="animation-delay: {{ $loop->index * 30 }}ms">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('transactions.edit', $transaction->id) }}" class="text-sm font-medium text-white transition-colors group-hover:text-emerald-400" wire:navigate>
                                {{ $transaction->name }}
                            </a>
                            <p class="mt-0.5 text-xs text-zinc-500">
                                {{ $transaction->date->format('l, j M Y') }}
                                @if ($transaction->category_label)
                                    <span class="ml-1.5 inline-flex items-center rounded-md bg-zinc-700/60 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-400">
                                        {{ $transaction->category_label }}
                                    </span>
                                @endif
                            </p>
                        </div>
                        <span class="flex-none text-sm font-semibold tabular-nums {{ $transaction->type === 'expense' ? 'text-red-400' : 'text-emerald-400' }}">
                            {{ $transaction->type === 'expense' ? '−' : '+' }}{{ CurrencyHelper::toRSD($transaction->getAmountInRsd($rates)) }}
                        </span>
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" class="opacity-0 transition-opacity group-hover:opacity-100" />
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
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <flux:icon.magnifying-glass class="size-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-300">No transactions found</p>
                    <p class="mt-0.5 text-xs text-zinc-500">Try adjusting your filters or search term</p>
                </div>
            </div>
        @endif

        {{-- Infinite Scroll Sentinel --}}
        @if ($transactions->hasMorePages())
            <div
                wire:key="load-more-{{ $this->perPage }}"
                x-intersect="$wire.loadMore()"
                class="flex justify-center py-4 sm:py-6"
            >
                <flux:icon.arrow-path class="size-5 animate-spin text-zinc-500" />
            </div>
        @endif
    </div>
</section>
