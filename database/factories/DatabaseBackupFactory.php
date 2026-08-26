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
            'disk' => 's3',
            'path' => 'database-backups/backup.backup',
            'filename' => 'backup.backup',
            'size' => 1024,
            'status' => 'completed',
            'generated_at' => now(),
            'error_message' => null,
        ];
    }
}
