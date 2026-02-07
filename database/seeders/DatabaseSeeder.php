<?php

namespace Database\Seeders;

use App\Models\user;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // user::factory(10)->create();

        user::factory()->create([
            'name' => 'Test user',
            'email' => 'test@example.com',
        ]);
    }
}
