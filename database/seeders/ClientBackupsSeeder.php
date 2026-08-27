<?php

namespace Database\Seeders;

use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\DatabaseBackup;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class ClientBackupsSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::query()->where('code', '512122')->firstOrFail();

        $connection = BackupConnection::query()->updateOrCreate(
            ['client_id' => $client->id, 'name' => 'Conexión de prueba'],
            [
                'project_id' => null,
                'ssh_host' => '127.0.0.1',
                'ssh_port' => 22,
                'ssh_user' => 'demo',
                'postgres_host' => 'localhost',
                'postgres_port' => 5432,
                'postgres_database' => 'demo_database',
                'postgres_user' => 'demo',
                'is_active' => false,
            ],
        );

        DatabaseBackup::query()
            ->where('backup_connection_id', $connection->id)
            ->where('filename', 'like', 'demo-client-backup-%')
            ->delete();

        $now = CarbonImmutable::now(config('backups.timezone'));
        $weekStart = $now->startOfWeek();

        for ($day = 0; $day <= $weekStart->diffInDays($now); $day++) {
            $date = $weekStart->addDays($day)->setTime(5, 0);
            $this->createBackup($connection, $date, "demo-client-backup-week-{$date->format('Y-m-d')}.backup");
        }

        $monthlyStart = $now->subMonths(11)->startOfMonth();
        $week = $monthlyStart->startOfWeek()->setTime(5, 0);

        while ($week->lessThan($weekStart)) {
            $this->createBackup($connection, $week, "demo-client-backup-monthly-{$week->format('Y-m-d')}.backup");
            $week = $week->addWeek();
        }

        $annualStart = $now->subYears((int) config('backups.retention.years', 5))->startOfMonth();
        $month = $annualStart->setTime(5, 0);

        while ($month->lessThan($monthlyStart)) {
            $this->createBackup($connection, $month, "demo-client-backup-annual-{$month->format('Y-m')}.backup");
            $month = $month->addMonth();
        }
    }

    private function createBackup(BackupConnection $connection, CarbonImmutable $generatedAt, string $filename): void
    {
        DatabaseBackup::query()->create([
            'client_id' => $connection->client_id,
            'project_id' => $connection->project_id,
            'backup_connection_id' => $connection->id,
            'disk' => config('backups.disk', 's3'),
            'path' => "database-backups/{$connection->client_id}/demo/{$filename}",
            'filename' => $filename,
            'size' => 5 * 1024 * 1024,
            'status' => 'completed',
            'generated_at' => $generatedAt,
        ]);
    }
}
