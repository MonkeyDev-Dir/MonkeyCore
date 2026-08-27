<?php

namespace Database\Factories;

use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\DatabaseBackup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DatabaseBackup>
 */
class DatabaseBackupFactory extends Factory
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
            'backup_connection_id' => BackupConnection::factory(),
            'execution_id' => fake()->uuid(),
            'disk' => 's3',
            'path' => 'database-backups/backup.backup',
            'filename' => 'backup.backup',
            'size' => 1024,
            'status' => 'completed',
            'command' => 'pg_dump --host=localhost --port=5432 --user=postgres --database=database',
            'exit_code' => 0,
            'attempts' => 1,
            'duration_ms' => 1000,
            'checksum' => str_repeat('a', 64),
            'started_at' => now()->subSecond(),
            'completed_at' => now(),
            'storage_verified_at' => now(),
            'generated_at' => now(),
            'error_message' => null,
            'error_output' => null,
            'metadata' => null,
        ];
    }
}
