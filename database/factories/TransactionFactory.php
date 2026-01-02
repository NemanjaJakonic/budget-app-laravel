<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['income', 'expense']);

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'type' => $type,
            'currency' => $this->faker->randomElement(['RSD', 'EUR', 'USD']),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'category' => $type === 'expense' ? $this->faker->randomElement(['bills', 'food', 'rest']) : null,
        ];
    }

    /**
     * Indicate that the transaction is an income.
     */
    public function income(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'income',
            'category' => null,
        ]);
    }

    /**
     * Indicate that the transaction is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'expense',
            'category' => $this->faker->randomElement(['bills', 'food', 'rest']),
        ]);
    }

    /**
     * Set a specific currency.
     */
    public function currency(string $currency): static
    {
        return $this->state(fn(array $attributes) => [
            'currency' => $currency,
        ]);
    }
}
