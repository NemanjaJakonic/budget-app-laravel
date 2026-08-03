<?php

use App\Helpers\CurrencyHelper;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public int $selectedYear;
    public array $years = [];
    public array $rates = [];
    public array $monthlyData = [];
    public array $yearlyTotal = ['income' => 0, 'expense' => 0, 'savings' => 0];

    public function mount(): void
    {
        $this->selectedYear = now()->year;
        $exchangeRateService = app(ExchangeRateService::class);
        $this->rates = $exchangeRateService->getRates();

        // Get unique years from transactions
        $transactions = Auth::user()->transactions;
        $this->years = $transactions
            ->pluck('date')
            ->map(fn($date) => $date->year)
            ->unique()
            ->sort()
            ->reverse()
            ->values()
            ->toArray();

        if (!in_array($this->selectedYear, $this->years) && count($this->years) > 0) {
            $this->selectedYear = $this->years[0];
        }

        $this->calculateData();
    }

    public function updatedSelectedYear(): void
    {
        $this->calculateData();
    }

    public function calculateData(): void
    {
        $this->monthlyData = [];
        $this->yearlyTotal = ['income' => 0, 'expense' => 0, 'savings' => 0];

        $transactions = Auth::user()->transactions()
            ->whereYear('date', $this->selectedYear)
            ->get();

        foreach ($transactions as $transaction) {
            $amountInEur = $transaction->getAmountInEur($this->rates);
            $month = $transaction->date->month - 1;

            if (!isset($this->monthlyData[$month])) {
                $this->monthlyData[$month] = ['income' => 0, 'expense' => 0, 'savings' => 0];
            }

            if ($transaction->type === 'income') {
                $this->monthlyData[$month]['income'] += $amountInEur;
                $this->yearlyTotal['income'] += $amountInEur;
            } else {
                $this->monthlyData[$month]['expense'] += $amountInEur;
                $this->yearlyTotal['expense'] += $amountInEur;
            }

            $this->monthlyData[$month]['savings'] = $this->monthlyData[$month]['income'] - $this->monthlyData[$month]['expense'];
        }

        $this->yearlyTotal['savings'] = $this->yearlyTotal['income'] - $this->yearlyTotal['expense'];
    }

    public function getMonths(): array
    {
        return [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
    }
}; ?>

<section class="w-full page-enter">
    <div class="mx-auto w-full max-w-4xl px-3 py-3 sm:px-0 sm:py-4">
        {{-- Header --}}
        <div class="flex items-center justify-between pb-3 sm:pb-4">
            <div>
                <h1 class="text-xl font-semibold text-white">Savings</h1>
                <p class="mt-0.5 text-sm text-zinc-500">Track your savings over time</p>
            </div>
            <div class="w-40">
                <flux:select wire:model.live="selectedYear">
                    @foreach ($years as $year)
                        <flux:select.option value="{{ $year }}">{{ $year }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        {{-- Yearly Summary --}}
        <div class="card mb-3 sm:mb-5">
            <p class="mb-3 text-sm font-medium text-zinc-300 sm:mb-4">{{ $selectedYear }} Summary</p>
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                <div class="rounded-lg bg-emerald-500/10 p-2 sm:p-3">
                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-500">Income</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-emerald-400">
                        {{ CurrencyHelper::toEUR($yearlyTotal['income']) }}
                    </p>
                </div>
                <div class="rounded-lg bg-red-500/10 p-2 sm:p-3">
                    <p class="text-xs font-medium uppercase tracking-wider text-red-500">Expenses</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-red-400">
                        {{ CurrencyHelper::toEUR($yearlyTotal['expense']) }}
                    </p>
                </div>
                <div class="rounded-lg bg-blue-500/10 p-2 sm:p-3">
                    <p class="text-xs font-medium uppercase tracking-wider text-blue-500">Savings</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-blue-400">
                        {{ CurrencyHelper::toEUR($yearlyTotal['savings']) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Monthly Breakdown --}}
        <div class="card">
            <p class="mb-3 text-sm font-medium text-zinc-300 sm:mb-4">Monthly Breakdown</p>

            @if (!empty($monthlyData))
                <div class="grid gap-2 sm:gap-3 md:grid-cols-2">
                    @foreach ($this->getMonths() as $index => $month)
                        @if (isset($monthlyData[$index]))
                            <div class="list-item-enter rounded-lg border border-zinc-700/40 bg-zinc-900/40 p-3 sm:p-4" style="animation-delay: {{ $index * 30 }}ms">
                                <h3 class="mb-2.5 text-sm font-medium text-zinc-300">{{ $month }}</h3>
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-zinc-500">Income</span>
                                        <span class="text-sm font-medium tabular-nums text-emerald-400">
                                            {{ CurrencyHelper::toEUR($monthlyData[$index]['income']) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-zinc-500">Expenses</span>
                                        <span class="text-sm font-medium tabular-nums text-red-400">
                                            {{ CurrencyHelper::toEUR($monthlyData[$index]['expense']) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between border-t border-zinc-700/40 pt-1.5">
                                        <span class="text-xs text-zinc-500">Savings</span>
                                        <span class="text-sm font-semibold tabular-nums text-blue-400">
                                            {{ CurrencyHelper::toEUR($monthlyData[$index]['savings']) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <flux:icon.chart-bar class="size-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-300">No transactions for {{ $selectedYear }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Add transactions to see your savings breakdown</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
