<?php

use App\Helpers\CurrencyHelper;
use App\Models\Transaction;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public float $totalBalanceRSD = 0;
    public float $totalBalanceEUR = 0;
    public float $currentMonthSavings = 0;
    public float $currentMonthSavingsEUR = 0;
    public float $savingsPercentage = 0;
    public array $rates = [];
    public array $recentTransactions = [];
    public array $monthlyData = [];
    public array $mobileMonthlyData = [];

    public function mount(): void
    {
        $user = Auth::user();
        $exchangeRateService = app(ExchangeRateService::class);
        $this->rates = $exchangeRateService->getRates();

        $profile = $user->getOrCreateProfile();
        $transactions = $user->transactions()->orderBy('date', 'desc')->get();

        // Calculate total balance
        $this->totalBalanceRSD = (float) $profile->starting_balance;
        foreach ($transactions as $transaction) {
            $amountInRsd = $transaction->getAmountInRsd($this->rates);
            if ($transaction->type === 'income') {
                $this->totalBalanceRSD += $amountInRsd;
            } else {
                $this->totalBalanceRSD -= $amountInRsd;
            }
        }
    
        $this->totalBalanceEUR = $this->totalBalanceRSD / $this->rates['RSD'];

        // Calculate current month savings
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $currentMonthIncome = 0;
        $currentMonthExpenses = 0;

        foreach ($transactions as $transaction) {
            $transactionDate = $transaction->date;
            if ($transactionDate->month === $currentMonth && $transactionDate->year === $currentYear) {
                $amountInRsd = $transaction->getAmountInRsd($this->rates);
                if ($transaction->type === 'income') {
                    $currentMonthIncome += $amountInRsd;
                } else {
                    $currentMonthExpenses += $amountInRsd;
                }
            }
        }

        $this->currentMonthSavings = $currentMonthIncome - $currentMonthExpenses;
        $this->currentMonthSavingsEUR = $this->currentMonthSavings / $this->rates['RSD'];
        $this->savingsPercentage = $currentMonthIncome > 0 ? ($this->currentMonthSavings / $currentMonthIncome) * 100 : 0;

        // Calculate monthly data for chart
        $monthlyTotals = array_fill(0, 12, ['income' => 0, 'expenses' => 0]);
        foreach ($transactions as $transaction) {
            $transactionDate = $transaction->date;
            if ($transactionDate->year === $currentYear) {
                $month = $transactionDate->month - 1;
                if ($transaction->type === 'income') {
                    $monthlyTotals[$month]['income'] += (float) $transaction->amount;
                } else {
                    $monthlyTotals[$month]['expenses'] += (float) $transaction->amount;
                }
            }
        }

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $this->monthlyData = array_values(array_map(
            fn ($index, $totals) => [
                'month' => $labels[$index],
                'income' => $totals['income'],
                'expenses' => $totals['expenses'],
            ],
            array_keys($monthlyTotals),
            $monthlyTotals
        ));

        $currentMonthIndex = now()->month - 1;
        $startIndex = max(0, $currentMonthIndex - 2);
        $endIndex = min(11, $currentMonthIndex + 2);
        $this->mobileMonthlyData = array_values(array_slice($this->monthlyData, $startIndex, $endIndex - $startIndex + 1));

        // Recent transactions
        $this->recentTransactions = $transactions->take(5)->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'amount' => (float) $t->amount,
            'currency' => $t->currency,
            'type' => $t->type,
            'date' => $t->date->format('l, j M Y'),
            'category' => $t->category,
            'category_label' => $t->category_label,
            'formatted_amount' => CurrencyHelper::format($t->amount, $t->currency),
        ])->toArray();
    }

    public function deleteTransaction(int $id): void
    {
        $transaction = Transaction::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($transaction) {
            $transaction->delete();
            $this->mount();
        }
    }
}; ?>

@php
    $chartData = $monthlyData;
    $maxValue = 0;
    foreach ($chartData as $d) {
        $maxValue = max($maxValue, $d['income'], $d['expenses']);
    }
    $maxValue = $maxValue > 0 ? $maxValue : 1;
@endphp

<section class="w-full page-enter">
    <div class="mx-auto w-full max-w-4xl space-y-2.5 sm:space-y-5 sm:px-0">
        {{-- Page Header --}}
        <div class="pt-1 sm:pt-2">
            <h1 class="text-xl font-semibold text-white">Dashboard</h1>
            <p class="mt-0.5 text-sm text-zinc-400">{{ now()->format('l, j F Y') }}</p>
        </div>

        {{-- Total Balance Card --}}
        <div class="card" role="region" aria-label="Total balance">
            <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">Total Balance</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-white">
                {{ CurrencyHelper::toRSD($totalBalanceRSD) }}
            </p>
            <p class="mt-1 text-base text-zinc-400">
                {{ CurrencyHelper::toEUR($totalBalanceEUR) }}
            </p>
        </div>

        {{-- Monthly Savings --}}
        <div class="card" role="region" aria-label="Monthly savings">
            <div class="flex items-baseline justify-between">
                <p class="text-sm font-medium text-zinc-300">{{ now()->format('F') }} Savings</p>
                <p class="text-sm font-semibold text-emerald-400">{{ CurrencyHelper::toEUR($currentMonthSavingsEUR) }}</p>
            </div>
            <div class="mt-3" role="progressbar" aria-valuenow="{{ round($savingsPercentage) }}" aria-valuemin="0" aria-valuemax="100" aria-label="Savings rate">
                <div class="relative h-2 w-full overflow-hidden rounded-full bg-zinc-700/60">
                    <div 
                        class="progress-bar-fill absolute inset-y-0 left-0 rounded-full bg-emerald-500/80"
                        style="width: {{ max(0, min(100, $savingsPercentage)) }}%;"
                    ></div>
                </div>
                <p class="mt-1.5 text-right text-xs text-zinc-500">{{ number_format($savingsPercentage, 1) }}% saved</p>
            </div>
        </div>

        {{-- Monthly Chart --}}
        <div class="card">
            <p class="mb-3 text-sm font-medium text-zinc-300 sm:mb-4">Income vs Expenses</p>

            {{-- Mobile: 5-month view --}}
            <div class="sm:hidden">
                <div class="flex items-end gap-1.5" style="height: 140px;">
                    @foreach ($mobileMonthlyData as $data)
                        @php
                            $incomeHeight = $maxValue > 0 ? ($data['income'] / $maxValue) * 120 : 0;
                            $expenseHeight = $maxValue > 0 ? ($data['expenses'] / $maxValue) * 120 : 0;
                        @endphp
                        <div class="flex flex-1 flex-col items-center gap-0.5">
                            <div class="flex items-end gap-px" style="height: 120px;">
                                <div class="w-2.5 rounded-t bg-emerald-500/80 transition-all" style="height: {{ max(2, $incomeHeight) }}px;" title="Income: {{ number_format($data['income'], 0) }}"></div>
                                <div class="w-2.5 rounded-t bg-red-500/80 transition-all" style="height: {{ max(2, $expenseHeight) }}px;" title="Expenses: {{ number_format($data['expenses'], 0) }}"></div>
                            </div>
                            <span class="text-[10px] text-zinc-500">{{ $data['month'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-center gap-4 pt-3">
                    <div class="flex items-center gap-1.5">
                        <div class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></div>
                        <span class="text-xs text-zinc-400">Income</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="h-2.5 w-2.5 rounded-sm bg-red-500"></div>
                        <span class="text-xs text-zinc-400">Expense</span>
                    </div>
                </div>
            </div>

            {{-- Desktop: full 12-month view --}}
            <div class="hidden sm:block">
                <div class="flex items-end gap-2" style="height: 200px;">
                    @foreach ($chartData as $data)
                        @php
                            $incomeHeight = $maxValue > 0 ? ($data['income'] / $maxValue) * 180 : 0;
                            $expenseHeight = $maxValue > 0 ? ($data['expenses'] / $maxValue) * 180 : 0;
                        @endphp
                        <div class="flex flex-1 flex-col items-center gap-0.5">
                            <div class="flex items-end gap-0.5" style="height: 180px;">
                                <div class="w-3.5 rounded-t bg-emerald-500/80 transition-all" style="height: {{ max(2, $incomeHeight) }}px;" title="Income: {{ number_format($data['income'], 0) }} RSD"></div>
                                <div class="w-3.5 rounded-t bg-red-500/80 transition-all" style="height: {{ max(2, $expenseHeight) }}px;" title="Expenses: {{ number_format($data['expenses'], 0) }} RSD"></div>
                            </div>
                            <span class="text-[11px] text-zinc-500">{{ $data['month'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-center gap-4 pt-3">
                    <div class="flex items-center gap-1.5">
                        <div class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></div>
                        <span class="text-xs text-zinc-400">Income</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="h-2.5 w-2.5 rounded-sm bg-red-500"></div>
                        <span class="text-xs text-zinc-400">Expense</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="card">
            <div class="mb-3 flex items-baseline justify-between sm:mb-4">
                <h2 class="text-sm font-medium text-zinc-300">Recent Transactions</h2>
                @if (count($recentTransactions) > 0)
                    <x-link href="{{ route('transactions.index') }}" wire:navigate class="text-xs">View all</x-link>
                @endif
            </div>

            @if (count($recentTransactions) > 0)
                <div class="space-y-1">
                    @foreach ($recentTransactions as $transaction)
                        <div wire:key="recent-{{ $transaction['id'] }}" class="list-item-enter group flex items-center gap-3 rounded-lg px-2.5 py-2 transition-colors hover:bg-zinc-700/40 sm:gap-4 sm:px-3 sm:py-2.5" style="animation-delay: {{ $loop->index * 40 }}ms">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('transactions.edit', $transaction['id']) }}" class="text-sm font-medium text-white transition-colors group-hover:text-emerald-400" wire:navigate>
                                    {{ $transaction['name'] }}
                                </a>
                                <p class="mt-0.5 text-xs text-zinc-500">
                                    {{ $transaction['date'] }}
                                    @if ($transaction['category_label'])
                                        <span class="ml-1.5 inline-flex items-center rounded-md bg-zinc-700/60 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-400">
                                            {{ $transaction['category_label'] }}
                                        </span>
                                    @endif
                                </p>
                            </div>
                            <span class="flex-none text-sm font-semibold tabular-nums {{ $transaction['type'] === 'expense' ? 'text-red-400' : 'text-emerald-400' }}">
                                {{ $transaction['type'] === 'expense' ? '−' : '+' }}{{ $transaction['formatted_amount'] }}
                            </span>
                            <x-dropdown position="bottom" align="end">
                                <x-slot name="trigger">
                                    <x-form.button variant="ghost" size="sm" icon="ellipsis-vertical" class="text-zinc-500 transition-colors hover:text-white" />
                                </x-slot>
                                <x-slot name="content">
                                    <x-menu-item :href="route('transactions.edit', $transaction['id'])" icon="pencil" wire:navigate>
                                        {{ __('Edit') }}
                                    </x-menu-item>
                                    <x-menu-item 
                                        wire:click="deleteTransaction({{ $transaction['id'] }})" 
                                        wire:confirm="Are you sure you want to delete this transaction?"
                                        icon="trash" 
                                        danger
                                    >
                                        {{ __('Delete') }}
                                    </x-menu-item>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <x-icon name="banknotes" class="size-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-300">No transactions yet</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Add your first transaction to get started</p>
                    </div>
                    <x-form.button href="{{ route('transactions.create') }}" variant="primary" size="sm" wire:navigate class="mt-1">
                        Add Transaction
                    </x-form.button>
                </div>
            @endif
        </div>
    </div>
</section>
