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

        $this->monthlyData = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'income' => array_column($monthlyTotals, 'income'),
            'expenses' => array_column($monthlyTotals, 'expenses'),
        ];

        // Recent transactions
        $this->recentTransactions = $transactions->take(3)->map(fn($t) => [
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

<section class="w-full">
    <div class="mx-auto w-full max-w-4xl space-y-6">
        {{-- Total Balance Card --}}
        <div class="rounded-xl bg-zinc-800/40 py-4">
            <p class="pb-2 text-xs font-semibold uppercase text-zinc-400">Total Balance</p>
            <h2 class="text-2xl font-extrabold uppercase text-white">
                {{ CurrencyHelper::toRSD($totalBalanceRSD) }}
            </h2>
            <h2 class="text-lg font-extrabold uppercase text-zinc-300">
                {{ CurrencyHelper::toEUR($totalBalanceEUR) }}
            </h2>
        </div>

        {{-- Monthly Savings --}}
        <div class="rounded-xl bg-zinc-800/40 py-4">
            <p class="pb-2 text-sm text-zinc-300">
                {{ now()->format('F') }} Savings:
                <span class="font-bold text-emerald-400">{{ CurrencyHelper::toEUR($currentMonthSavingsEUR) }}</span>
            </p>
            <div class="relative w-full rounded border border-zinc-700">
                <div class="absolute left-1/2 flex h-full -translate-x-1/2 items-center font-bold text-white">
                    {{ number_format($savingsPercentage, 2) }}%
                </div>
                <div 
                    class="flex h-6 items-center justify-center rounded-l bg-emerald-500/50"
                    style="width: {{ max(0, min(100, $savingsPercentage)) }}%;"
                ></div>
            </div>
        </div>

        {{-- Monthly Chart --}}
        <div class="rounded-xl bg-zinc-800/40 py-4">
            <div class="flex justify-center gap-4 pb-4">
                <div class="flex items-center gap-2">
                    <span class="block h-3 w-12 rounded bg-emerald-500"></span>
                    <span class="text-sm text-zinc-400">Income</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="block h-3 w-12 rounded bg-red-500"></span>
                    <span class="text-sm text-zinc-400">Expense</span>
                </div>
            </div>
            <canvas id="monthlyChart" wire:ignore></canvas>
        </div>

        {{-- Recent Transactions --}}
        <div>
            <h3 class="pb-4 text-center text-sm font-bold text-white">Recent Transactions</h3>
            <div class="space-y-3">
                @forelse ($recentTransactions as $transaction)
                    <div class="flex items-center gap-4 rounded-xl bg-zinc-800/40 py-4">
                        <div class="flex-1">
                            <a href="{{ route('transactions.edit', $transaction['id']) }}" class="text-sm text-white hover:text-emerald-400" wire:navigate>
                                {{ $transaction['name'] }}
                            </a>
                            <p class="text-xs text-zinc-400">
                                {{ $transaction['date'] }}
                                @if ($transaction['category_label'])
                                    <span class="ml-2 rounded bg-emerald-500/20 px-1.5 py-0.5 text-xs text-emerald-400">
                                        {{ $transaction['category_label'] }}
                                    </span>
                                @endif
                            </p>
                        </div>
                        <span class="flex-1 text-right text-sm font-semibold {{ $transaction['type'] === 'expense' ? 'text-red-400' : 'text-emerald-400' }}">
                            {{ $transaction['type'] === 'expense' ? '-' : '' }}{{ $transaction['formatted_amount'] }}
                        </span>
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item :href="route('transactions.edit', $transaction['id'])" icon="pencil" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:menu.item>
                                <flux:menu.item 
                                    wire:click="deleteTransaction({{ $transaction['id'] }})" 
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
                    <p class="py-8 text-center text-zinc-500">No transactions yet</p>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        function initChart() {
            // Wait for Chart.js to be available
            if (typeof Chart === 'undefined') {
                setTimeout(initChart, 100);
                return;
            }

            const ctx = document.getElementById('monthlyChart');
            if (!ctx) {
                return;
            }

            // Destroy existing chart if it exists
            if (ctx.chart) {
                ctx.chart.destroy();
                ctx.chart = null;
            }

            ctx.chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($monthlyData['labels']),
                        datasets: [
                            {
                                label: 'Income',
                                data: @json($monthlyData['income']),
                                backgroundColor: '#10b981',
                                borderRadius: 4,
                                borderSkipped: false
                            },
                            {
                                label: 'Expenses',
                                data: @json($monthlyData['expenses']),
                                backgroundColor: '#ef4444',
                                borderRadius: 4,
                                borderSkipped: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#9ca3af' },
                                grid: { color: '#374151' }
                            },
                            x: {
                                ticks: { color: '#9ca3af' },
                                grid: { color: '#374151' }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        }

        document.addEventListener('livewire:navigated', function() {
            initChart();
        });
        document.addEventListener('DOMContentLoaded', function() {
            initChart();
        });
    </script>
</section>
