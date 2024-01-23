<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class eventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start_time = Carbon::now();
        $end_time = Carbon::parse($start_time)->addDays(7);
        return [
            'name' => fake()->name(),
            'location' => fake()->unique()->address(),
            'banner' => 'http://127.0.0.1:8000/Upload/1702785355.jpg',
            'contact'=>fake()->unique()->phoneNumber(),
            'status' => fake()->numberBetween(0,1),
            'user_id' => fake()->numberBetween(1,30),
//            'description' => 'Sự kiện đẹp',
            'content' => '1 Buổi hòa nhạc đặc biệt',
            'start_time' =>$start_time,
            'end_time' =>$end_time,
            ];
    }
}
