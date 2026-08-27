<?php

namespace App\Jobs;

use App\Models\DatabaseBackup;
use App\Services\DatabaseBackupService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessDatabaseBackup implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $tries = 3;

    public int $timeout = 3900;

    public array $backoff = [60, 300, 900];

    public int $uniqueFor = 3600;

    public function __construct(public int $backupId) {}

    public function uniqueId(): string
    {
        return "database-backup:{$this->backupId}";
    }

    public function handle(DatabaseBackupService $backupService): void
    {
        $backup = DatabaseBackup::query()
            ->with('backupConnection')
            ->findOrFail($this->backupId);

        if ($backup->status === 'completed') {
            return;
        }

        $startedAt = microtime(true);
        $backup->update([
            'status' => 'running',
            'started_at' => now(),
            'error_message' => null,
            'error_output' => null,
        ]);
        $backup->increment('attempts');

        try {
            $backupService->createForConnection($backup->backupConnection, $backup);
        } catch (Throwable $exception) {
            $duration = (int) round((microtime(true) - $startedAt) * 1000);
            $backup->update([
                'status' => 'failed',
                'duration_ms' => $duration,
                'completed_at' => now(),
                'error_message' => Str::limit($exception->getMessage(), 2000),
                'error_output' => Str::limit($exception->getMessage(), 10000),
                'metadata' => [
                    'exception' => $exception::class,
                    'trace' => Str::limit($exception->getTraceAsString(), 10000),
                ],
            ]);

            Log::channel('backups')->error('Job de respaldo fallido', [
                'backup_id' => $backup->id,
                'backup_connection_id' => $backup->backup_connection_id,
                'duration_ms' => $duration,
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $backup = DatabaseBackup::query()->find($this->backupId);

        if ($backup === null || $backup->status === 'completed') {
            return;
        }

        $backup->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $exception?->getMessage() ?? $backup->error_message,
            'error_output' => $exception?->getMessage() ?? $backup->error_output,
        ]);
    }
}
