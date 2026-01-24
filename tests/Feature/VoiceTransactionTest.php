<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\DeepseekService;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create();

    // Set Deepseek config for tests
    config([
        'services.deepseek.api_key' => 'test-api-key',
        'services.deepseek.base_url' => 'https://api.deepseek.com',
    ]);
});

test('voice transaction page shows mode toggle', function () {
    $this->actingAs($this->user)
        ->get(route('transactions.create'))
        ->assertOk()
        ->assertSee('Standard')
        ->assertSee('Voice');
});

test('parseVoiceInput populates form with parsed data', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'name' => 'coffee',
                            'amount' => 250,
                            'type' => 'expense',
                            'currency' => 'RSD',
                            'category' => 'food',
                        ]),
                    ],
                ],
            ],
        ]),
    ]);

    Volt::actingAs($this->user)
        ->test('budget.transactions.create')
        ->call('parseVoiceInput', 'coffee 250 dinars food')
        ->assertSet('name', 'coffee')
        ->assertSet('amount', 250.0)
        ->assertSet('type', 'expense')
        ->assertSet('currency', 'RSD')
        ->assertSet('category', 'food')
        ->assertSet('voiceError', '');
});

test('parseVoiceInput returns error when amount is missing', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'name' => 'groceries',
                            'amount' => null,
                            'type' => 'expense',
                            'currency' => 'RSD',
                            'category' => 'food',
                        ]),
                    ],
                ],
            ],
        ]),
    ]);

    Volt::actingAs($this->user)
        ->test('budget.transactions.create')
        ->call('parseVoiceInput', 'groceries food')
        ->assertSet('voiceError', 'Amount is required. Please try again and include an amount.');
});

test('parseVoiceInput handles income type correctly', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'name' => 'salary',
                            'amount' => 1500,
                            'type' => 'income',
                            'currency' => 'EUR',
                            'category' => null,
                        ]),
                    ],
                ],
            ],
        ]),
    ]);

    Volt::actingAs($this->user)
        ->test('budget.transactions.create')
        ->call('parseVoiceInput', 'salary 1500 euros income')
        ->assertSet('name', 'salary')
        ->assertSet('amount', 1500.0)
        ->assertSet('type', 'income')
        ->assertSet('currency', 'EUR')
        ->assertSet('category', null);
});

test('parseVoiceInput uses default values for missing optional fields', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'name' => 'lunch',
                            'amount' => 800,
                            'type' => null,
                            'currency' => null,
                            'category' => null,
                        ]),
                    ],
                ],
            ],
        ]),
    ]);

    Volt::actingAs($this->user)
        ->test('budget.transactions.create')
        ->assertSet('type', 'expense')
        ->assertSet('currency', 'RSD')
        ->call('parseVoiceInput', 'lunch 800')
        ->assertSet('name', 'lunch')
        ->assertSet('amount', 800.0)
        ->assertSet('type', 'expense')
        ->assertSet('currency', 'RSD');
});

test('parseVoiceInput handles API error gracefully', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response(['error' => 'Invalid request'], 500),
    ]);

    Volt::actingAs($this->user)
        ->test('budget.transactions.create')
        ->call('parseVoiceInput', 'coffee 250')
        ->assertSet('voiceError', 'Failed to parse transaction. Please try again.');
});

test('parseVoiceInput handles empty transcript', function () {
    Volt::actingAs($this->user)
        ->test('budget.transactions.create')
        ->call('parseVoiceInput', '')
        ->assertSet('voiceError', 'Empty transcript provided.');
});

test('DeepseekService returns correct structure for valid input', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'name' => 'kafa',
                            'amount' => 250,
                            'type' => 'expense',
                            'currency' => 'RSD',
                            'category' => 'food',
                        ]),
                    ],
                ],
            ],
        ]),
    ]);

    $service = new DeepseekService;
    $result = $service->parseTransaction('kafa 250 dinara');

    expect($result)->toHaveKeys(['name', 'amount', 'type', 'currency', 'category', 'error'])
        ->and($result['name'])->toBe('kafa')
        ->and($result['amount'])->toBe(250.0)
        ->and($result['type'])->toBe('expense')
        ->and($result['currency'])->toBe('RSD')
        ->and($result['category'])->toBe('food')
        ->and($result['error'])->toBeNull();
});

test('DeepseekService handles connection timeout', function () {
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
    });

    $service = new DeepseekService;
    $result = $service->parseTransaction('test input');

    expect($result['error'])->toBe('Connection error. Please try again.');
});

test('voice transaction can be submitted after parsing', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'name' => 'dinner',
                            'amount' => 1200,
                            'type' => 'expense',
                            'currency' => 'RSD',
                            'category' => 'food',
                        ]),
                    ],
                ],
            ],
        ]),
    ]);

    Volt::actingAs($this->user)
        ->test('budget.transactions.create')
        ->call('parseVoiceInput', 'dinner 1200 dinars food')
        ->set('date', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('transactions', [
        'user_id' => $this->user->id,
        'name' => 'dinner',
        'amount' => 1200,
        'type' => 'expense',
        'currency' => 'RSD',
        'category' => 'food',
    ]);
});
