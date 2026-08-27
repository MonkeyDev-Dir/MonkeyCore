<?php

namespace Database\Factories;

use App\Models\BackupDatabaseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BackupDatabaseType>
 */
class BackupDatabaseTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(1),
            'name' => fake()->unique()->words(2, true),
            'backup_command' => 'pg_dump',
            'is_active' => true,
        ];
    }
}
