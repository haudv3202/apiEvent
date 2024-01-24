<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\feedback>
 */
class feedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->unique()->text(),
            'user_id' => fake()->numberBetween(1,30),
            'event_id' => fake()->unique()->numberBetween(1,30),
            'rating' => fake()->numberBetween(1,5)
        ];
    }
}
