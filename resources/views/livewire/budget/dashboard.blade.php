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
            {{-- Mobile: 5-month view --}}
            <flux:chart wire:model="mobileMonthlyData" class="sm:hidden">
                <flux:chart.viewport class="aspect-[3/1]">
                    <flux:chart.svg>
                        <flux:chart.group>
                            <flux:chart.bar field="income" class="text-emerald-500" />
                            <flux:chart.bar field="expenses" class="text-red-500" />
                        </flux:chart.group>
                        <flux:chart.axis axis="x" field="month">
                            <flux:chart.axis.tick />
                            <flux:chart.axis.line />
                        </flux:chart.axis>
                        <flux:chart.axis axis="y">
                            <flux:chart.axis.grid class="text-zinc-700" />
                            <flux:chart.axis.tick class="text-zinc-400" />
                        </flux:chart.axis>
                        <flux:chart.cursor />
                    </flux:chart.svg>
                </flux:chart.viewport>
                <div class="flex justify-center gap-4 pt-4">
                    <flux:chart.legend label="Income">
                        <flux:chart.legend.indicator class="bg-emerald-500" />
                    </flux:chart.legend>
                    <flux:chart.legend label="Expense">
                        <flux:chart.legend.indicator class="bg-red-500" />
                    </flux:chart.legend>
                </div>
                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="month" />
                    <flux:chart.tooltip.value field="income" label="Income" :format="['style' => 'decimal', 'maximumFractionDigits' => 0]" suffix=" RSD" />
                    <flux:chart.tooltip.value field="expenses" label="Expenses" :format="['style' => 'decimal', 'maximumFractionDigits' => 0]" suffix=" RSD" />
                </flux:chart.tooltip>
            </flux:chart>

            {{-- Desktop: full 12-month view --}}
            <flux:chart wire:model="monthlyData" class="hidden sm:block">
                <flux:chart.viewport class="aspect-[3/1]">
                    <flux:chart.svg>
                        <flux:chart.group>
                            <flux:chart.bar field="income" class="text-emerald-500" />
                            <flux:chart.bar field="expenses" class="text-red-500" />
                        </flux:chart.group>
                        <flux:chart.axis axis="x" field="month">
                            <flux:chart.axis.tick />
                            <flux:chart.axis.line />
                        </flux:chart.axis>
                        <flux:chart.axis axis="y">
                            <flux:chart.axis.grid class="text-zinc-700" />
                            <flux:chart.axis.tick class="text-zinc-400" />
                        </flux:chart.axis>
                        <flux:chart.cursor />
                    </flux:chart.svg>
                </flux:chart.viewport>
                <div class="flex justify-center gap-4 pt-4">
                    <flux:chart.legend label="Income">
                        <flux:chart.legend.indicator class="bg-emerald-500" />
                    </flux:chart.legend>
                    <flux:chart.legend label="Expense">
                        <flux:chart.legend.indicator class="bg-red-500" />
                    </flux:chart.legend>
                </div>
                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="month" />
                    <flux:chart.tooltip.value field="income" label="Income" :format="['style' => 'decimal', 'maximumFractionDigits' => 0]" suffix=" RSD" />
                    <flux:chart.tooltip.value field="expenses" label="Expenses" :format="['style' => 'decimal', 'maximumFractionDigits' => 0]" suffix=" RSD" />
                </flux:chart.tooltip>
            </flux:chart>
        </div>

        {{-- Recent Transactions --}}
        <div>
            <h3 class="pb-4 text-center text-sm font-bold text-white">Recent Transactions</h3>
            <div class="space-y-3">
                @forelse ($recentTransactions as $transaction)
                    <div class="flex items-center gap-4 rounded-xl bg-zinc-800/40 py-2">
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
                        <span class="flex-none text-right text-sm font-semibold {{ $transaction['type'] === 'expense' ? 'text-red-400' : 'text-emerald-400' }}">
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

</section>
