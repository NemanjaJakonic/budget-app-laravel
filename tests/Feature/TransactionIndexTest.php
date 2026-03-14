<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('transactions index page loads', function () {
    $this->get(route('transactions.index'))
        ->assertOk();
});

test('it filters transactions by type', function () {
    Transaction::factory()->income()->create([
        'user_id' => $this->user->id,
        'name' => 'Salary Payment',
        'date' => now(),
    ]);
    Transaction::factory()->expense()->create([
        'user_id' => $this->user->id,
        'name' => 'Grocery Shopping',
        'date' => now(),
    ]);

    Volt::test('budget.transactions.index')
        ->set('selectedMonth', 0)
        ->set('selectedYear', 0)
        ->set('selectedType', 'income')
        ->assertSee('Salary Payment')
        ->assertDontSee('Grocery Shopping');

    Volt::test('budget.transactions.index')
        ->set('selectedMonth', 0)
        ->set('selectedYear', 0)
        ->set('selectedType', 'expense')
        ->assertSee('Grocery Shopping')
        ->assertDontSee('Salary Payment');
});

test('it filters transactions by category', function () {
    Transaction::factory()->expense()->create([
        'user_id' => $this->user->id,
        'name' => 'Electric Bill',
        'category' => 'bills',
        'date' => now(),
    ]);
    Transaction::factory()->expense()->create([
        'user_id' => $this->user->id,
        'name' => 'Pizza Delivery',
        'category' => 'food',
        'date' => now(),
    ]);

    Volt::test('budget.transactions.index')
        ->set('selectedMonth', 0)
        ->set('selectedYear', 0)
        ->set('selectedCategory', 'bills')
        ->assertSee('Electric Bill')
        ->assertDontSee('Pizza Delivery');
});

test('it searches transactions by name', function () {
    Transaction::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Netflix Subscription',
        'date' => now(),
    ]);
    Transaction::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Coffee Shop',
        'date' => now(),
    ]);

    Volt::test('budget.transactions.index')
        ->set('selectedMonth', 0)
        ->set('selectedYear', 0)
        ->set('search', 'Netflix')
        ->assertSee('Netflix Subscription')
        ->assertDontSee('Coffee Shop');
});

test('it combines search with filters', function () {
    Transaction::factory()->expense()->create([
        'user_id' => $this->user->id,
        'name' => 'Electric Bill',
        'category' => 'bills',
        'date' => now(),
    ]);
    Transaction::factory()->expense()->create([
        'user_id' => $this->user->id,
        'name' => 'Electric Scooter Purchase',
        'category' => 'rest',
        'date' => now(),
    ]);

    Volt::test('budget.transactions.index')
        ->set('selectedMonth', 0)
        ->set('selectedYear', 0)
        ->set('search', 'Electric')
        ->set('selectedCategory', 'bills')
        ->assertSee('Electric Bill')
        ->assertDontSee('Electric Scooter Purchase');
});

test('it loads more transactions on loadMore', function () {
    Transaction::factory()->count(20)->create([
        'user_id' => $this->user->id,
        'date' => now(),
    ]);

    Volt::test('budget.transactions.index')
        ->set('selectedMonth', 0)
        ->set('selectedYear', 0)
        ->assertSet('perPage', 15)
        ->call('loadMore')
        ->assertSet('perPage', 30);
});

test('updating search resets perPage', function () {
    Volt::test('budget.transactions.index')
        ->call('loadMore')
        ->assertSet('perPage', 30)
        ->set('search', 'test')
        ->assertSet('perPage', 15);
});

test('updating type filter resets perPage', function () {
    Volt::test('budget.transactions.index')
        ->call('loadMore')
        ->assertSet('perPage', 30)
        ->set('selectedType', 'expense')
        ->assertSet('perPage', 15);
});

test('updating category filter resets perPage', function () {
    Volt::test('budget.transactions.index')
        ->call('loadMore')
        ->assertSet('perPage', 30)
        ->set('selectedCategory', 'bills')
        ->assertSet('perPage', 15);
});

test('it shows empty state when no transactions match', function () {
    Volt::test('budget.transactions.index')
        ->set('selectedMonth', 0)
        ->set('selectedYear', 0)
        ->assertSee('No transactions found for the selected filters');
});

test('it only shows transactions for the authenticated user', function () {
    $otherUser = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My Transaction',
        'date' => now(),
    ]);
    Transaction::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other User Transaction',
        'date' => now(),
    ]);

    Volt::test('budget.transactions.index')
        ->set('selectedMonth', 0)
        ->set('selectedYear', 0)
        ->assertSee('My Transaction')
        ->assertDontSee('Other User Transaction');
});

test('it shows totals summary for filtered transactions', function () {
    Transaction::factory()->income()->create([
        'user_id' => $this->user->id,
        'name' => 'Salary',
        'amount' => 1000.00,
        'currency' => 'EUR',
        'date' => now(),
    ]);
    Transaction::factory()->expense()->create([
        'user_id' => $this->user->id,
        'name' => 'Rent',
        'amount' => 400.00,
        'currency' => 'EUR',
        'date' => now(),
    ]);

    Volt::test('budget.transactions.index')
        ->set('selectedMonth', 0)
        ->set('selectedYear', 0)
        ->assertSee('Summary')
        ->assertSee('Income')
        ->assertSee('Expenses')
        ->assertSee('Net');
});

test('totals respect active filters', function () {
    Transaction::factory()->income()->create([
        'user_id' => $this->user->id,
        'name' => 'Salary',
        'amount' => 1000.00,
        'currency' => 'EUR',
        'date' => now(),
    ]);
    Transaction::factory()->expense()->create([
        'user_id' => $this->user->id,
        'name' => 'Rent',
        'amount' => 400.00,
        'currency' => 'EUR',
        'date' => now(),
    ]);

    $component = Volt::test('budget.transactions.index')
        ->set('selectedMonth', 0)
        ->set('selectedYear', 0)
        ->set('selectedType', 'income');

    $component->assertSee('Salary')
        ->assertDontSee('Rent');
});
