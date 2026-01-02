<?php

use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\Api\ExpensesByCategoryController;
use App\Http\Controllers\Api\ExportTransactionsController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Budget App Routes (authenticated)
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard (replaces the old one)
    Volt::route('dashboard', 'budget.dashboard')->name('dashboard');

    // Transactions
    Volt::route('transactions', 'budget.transactions.index')->name('transactions.index');
    Volt::route('transactions/create', 'budget.transactions.create')->name('transactions.create');
    Volt::route('transactions/{transaction}/edit', 'budget.transactions.edit')->name('transactions.edit');

    // Savings
    Volt::route('savings', 'budget.savings')->name('savings');

    // Budget Profile
    Volt::route('budget-profile', 'budget.profile')->name('budget-profile');

    // Export API (requires auth)
    Route::get('api/export-transactions', ExportTransactionsController::class)->name('api.export-transactions');
});

// Public API routes
Route::prefix('api')->group(function () {
    Route::get('exchange-rates', [ExchangeRateController::class, 'index'])->name('api.exchange-rates');
    Route::post('exchange-rates/refresh', [ExchangeRateController::class, 'refresh'])->name('api.exchange-rates.refresh');
    Route::get('expenses-by-category', ExpensesByCategoryController::class)->name('api.expenses-by-category');
});

// Settings routes
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
