<?php

namespace Database\Factories;

use App\Models\BackupConnection;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BackupConnection>
 */
class BackupConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'project_id' => null,
            'name' => fake()->unique()->words(2, true),
            'ssh_host' => fake()->ipv4(),
            'ssh_port' => 22,
            'ssh_user' => 'forge',
            'postgres_host' => 'localhost',
            'postgres_port' => 5432,
            'postgres_database' => fake()->slug(2),
            'postgres_user' => 'postgres',
            'postgres_password' => fake()->password(),
            'is_active' => true,
        ];
    }
}
