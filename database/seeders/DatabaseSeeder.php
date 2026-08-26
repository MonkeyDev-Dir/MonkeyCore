<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Gilberth',
            'lastname' => 'Rojas Alfaro',
            'ide' => '113420689',
            'email' => 'me@gilberthrojas.com',
            'email_verified_at' => now(),
            'password' => 'Monkey#1',
        ]);

        User::factory()
            ->count(10)
            ->create();
    }
}
