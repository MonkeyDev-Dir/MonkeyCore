<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateDatabaseBackupCommand extends Command
{
    protected $signature = 'backups:create';

    protected $description = 'Encola y procesa respaldos remotos de bases de datos en S3';

    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('Encolando respaldos remotos de bases de datos...');
        $summary = $backupService->createAll();

        $this->info("Respaldos completados: {$summary['completed']}");
        $this->warn("Respaldos fallidos: {$summary['failed']}");
        $this->info("Respaldos encolados: {$summary['queued']}");

        Log::channel('backups')->info('Comando de respaldos finalizado', [
            'completed' => $summary['completed'],
            'failed' => $summary['failed'],
            'queued' => $summary['queued'],
        ]);

        return $summary['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
