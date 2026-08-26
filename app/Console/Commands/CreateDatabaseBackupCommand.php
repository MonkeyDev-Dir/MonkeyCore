<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateDatabaseBackupCommand extends Command
{
    protected $signature = 'backups:create';

    protected $description = 'Genera y almacena respaldos remotos de PostgreSQL en S3';

    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('Generando respaldos remotos de PostgreSQL...');
        $summary = $backupService->createAll();

        $this->info("Respaldos completados: {$summary['completed']}");
        $this->warn("Respaldos fallidos: {$summary['failed']}");

        Log::channel('backups')->info('Comando de respaldos finalizado', [
            'completed' => $summary['completed'],
            'failed' => $summary['failed'],
        ]);

        return $summary['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
