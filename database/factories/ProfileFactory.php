<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'starting_balance' => $this->faker->randomFloat(2, 0, 100000),
        ];
    }

    /**
     * Indicate that the profile has no starting balance.
     */
    public function empty(): static
    {
        return $this->state(fn(array $attributes) => [
            'starting_balance' => 0,
        ]);
    }
}
