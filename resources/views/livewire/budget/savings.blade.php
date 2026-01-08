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
            // Convert amount to EUR
            $amountInEur = $transaction->getAmountInEur($this->rates);

            $month = $transaction->date->month - 1; // 0-indexed

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

<section class="w-full">
    <div class="mx-auto w-full max-w-4xl py-4">
        <h1 class="pb-4 text-center text-lg font-bold text-white">Savings</h1>

        {{-- Year Selector --}}
        <div class="pb-4">
            <flux:select wire:model.live="selectedYear" class="w-48">
                @foreach ($years as $year)
                    <flux:select.option value="{{ $year }}">{{ $year }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Yearly Summary --}}
        <div class="mb-6 rounded-xl bg-zinc-800/40 py-4">
            <h2 class="mb-4 text-base font-semibold text-white">Yearly Summary - {{ $selectedYear }}</h2>
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-lg bg-emerald-500/10 p-3">
                    <p class="text-sm text-emerald-400">Total Income</p>
                    <p class="text-lg font-bold text-emerald-400">
                        {{ CurrencyHelper::toEUR($yearlyTotal['income']) }}
                    </p>
                </div>
                <div class="rounded-lg bg-red-500/10 p-3">
                    <p class="text-sm text-red-400">Total Expenses</p>
                    <p class="text-lg font-bold text-red-400">
                        {{ CurrencyHelper::toEUR($yearlyTotal['expense']) }}
                    </p>
                </div>
                <div class="rounded-lg bg-blue-500/10 p-3">
                    <p class="text-sm text-blue-400">Total Savings</p>
                    <p class="text-lg font-bold text-blue-400">
                        {{ CurrencyHelper::toEUR($yearlyTotal['savings']) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Monthly Breakdown --}}
        <div class="rounded-xl bg-zinc-800/40 py-4">
            <h2 class="mb-4 text-base font-semibold text-white">Monthly Breakdown</h2>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($this->getMonths() as $index => $month)
                    @if (isset($monthlyData[$index]))
                        <div class="rounded-lg bg-zinc-900/50 p-4">
                            <h3 class="mb-3 font-medium text-zinc-300">{{ $month }}</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-emerald-400">Income:</span>
                                    <span class="font-medium text-emerald-400">
                                        {{ CurrencyHelper::toEUR($monthlyData[$index]['income']) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-red-400">Expenses:</span>
                                    <span class="font-medium text-red-400">
                                        {{ CurrencyHelper::toEUR($monthlyData[$index]['expense']) }}
                                    </span>
                                </div>
                                <div class="flex justify-between border-t border-zinc-700 pt-2">
                                    <span class="text-blue-400">Savings:</span>
                                    <span class="font-medium text-blue-400">
                                        {{ CurrencyHelper::toEUR($monthlyData[$index]['savings']) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @if (empty($monthlyData))
                <p class="py-8 text-center text-zinc-500">No transactions for {{ $selectedYear }}</p>
            @endif
        </div>
    </div>
</section>
