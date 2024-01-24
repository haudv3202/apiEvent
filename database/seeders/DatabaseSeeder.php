<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\feedback;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         \App\Models\User::factory(30)->create();

        \App\Models\event::factory(30)->create();
        \App\Models\area::factory(1)->create();
        \App\Models\feedback::factory(30)->create();
//         \App\Models\User::factory()->create([
//             'name' => 'Test User',
//             'email' => 'test@example.com',
//             'phone' => '0353786736',
//             "role" => 1
//         ]);
    }
}
