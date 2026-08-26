<?php

namespace App\Services;

use App\Models\BackupConnection;
use App\Models\DatabaseBackup;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    /** @return array{completed: int, failed: int} */
    public function createAll(): array
    {
        $summary = ['completed' => 0, 'failed' => 0];
        $startedAt = microtime(true);
        $activeConnections = BackupConnection::query()->where('is_active', true)->count();

        Log::channel('backups')->info('Proceso de respaldos iniciado', [
            'active_connections' => $activeConnections,
            'disk' => $this->disk(),
            'path' => config('backups.path'),
        ]);

        BackupConnection::query()
            ->with(['client', 'project'])
            ->where('is_active', true)
            ->each(function (BackupConnection $connection) use (&$summary): void {
                $connectionStartedAt = microtime(true);
                $context = $this->connectionContext($connection);

                Log::channel('backups')->info('Respaldo de conexión iniciado', $context);

                try {
                    $backup = $this->createForConnection($connection);
                    $summary['completed']++;

                    Log::channel('backups')->info('Respaldo de conexión completado', $context + [
                        'backup_id' => $backup->id,
                        'path' => $backup->path,
                        'size' => $backup->size,
                        'duration_ms' => $this->durationInMilliseconds($connectionStartedAt),
                    ]);
                } catch (\Throwable $exception) {
                    $summary['failed']++;
                    $this->recordFailure($connection, $exception);
                    Log::channel('backups')->error('Respaldo de conexión fallido', $context + [
                        'duration_ms' => $this->durationInMilliseconds($connectionStartedAt),
                        'exception' => $exception::class,
                        'error' => $exception->getMessage(),
                    ]);
                    report($exception);
                }
            });

        $this->prune();

        Log::channel('backups')->info('Proceso de respaldos completado', [
            'completed' => $summary['completed'],
            'failed' => $summary['failed'],
            'duration_ms' => $this->durationInMilliseconds($startedAt),
        ]);

        return $summary;
    }

    public function createForConnection(BackupConnection $connection): DatabaseBackup
    {
        $startedAt = microtime(true);
        $context = $this->connectionContext($connection);

        Log::channel('backups')->debug('Preparación del respaldo iniciada', $context + [
            'ssh_key_configured' => $connection->ssh_private_key !== null && $connection->ssh_private_key !== '',
            'postgres_password_configured' => $connection->postgres_password !== null && $connection->postgres_password !== '',
        ]);

        $temporaryPath = tempnam(storage_path('app'), 'database-backup-');

        if ($temporaryPath === false) {
            throw new RuntimeException('No fue posible crear el archivo temporal del respaldo.');
        }

        $keyPath = null;

        try {
            $keyPath = $this->writePrivateKey($connection);
            $this->dumpRemoteDatabase($connection, $temporaryPath, $keyPath);

            $timestamp = CarbonImmutable::now(config('backups.timezone'));
            $name = "backup-{$timestamp->format('Y-m-d-His')}.backup";
            $path = trim(config('backups.path').'/'.$connection->client_id.'/'.$connection->id.'/'.$name, '/');
            $disk = Storage::disk($this->disk());
            $stream = fopen($temporaryPath, 'rb');

            if ($stream === false) {
                throw new RuntimeException('No fue posible leer el respaldo generado.');
            }

            try {
                Log::channel('backups')->info('Carga del respaldo a almacenamiento iniciada', $context + [
                    'disk' => $this->disk(),
                    'path' => $path,
                ]);

                if (! $disk->put($path, $stream, ['visibility' => 'private', 'ContentType' => 'application/octet-stream', 'ServerSideEncryption' => 'AES256'])) {
                    throw new RuntimeException('Amazon S3 no confirmó la carga del respaldo.');
                }
            } finally {
                fclose($stream);
            }

            $size = $disk->size($path);

            Log::channel('backups')->info('Carga del respaldo a almacenamiento completada', $context + [
                'disk' => $this->disk(),
                'path' => $path,
                'size' => $size,
            ]);

            $backup = DatabaseBackup::query()->create([
                'client_id' => $connection->client_id,
                'project_id' => $connection->project_id,
                'backup_connection_id' => $connection->id,
                'disk' => $this->disk(),
                'path' => $path,
                'filename' => $name,
                'size' => $size,
                'status' => 'completed',
                'generated_at' => $timestamp,
            ]);

            Log::channel('backups')->debug('Registro del respaldo persistido', $context + [
                'backup_id' => $backup->id,
                'duration_ms' => $this->durationInMilliseconds($startedAt),
            ]);

            return $backup;
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }

            if ($keyPath !== null && is_file($keyPath)) {
                unlink($keyPath);
            }
        }
    }

    public function create(): string
    {
        $temporaryPath = tempnam(storage_path('app'), 'database-backup-');

        if ($temporaryPath === false) {
            throw new RuntimeException('No fue posible crear el archivo temporal del respaldo.');
        }

        try {
            $this->dumpDatabase($temporaryPath);

            $timestamp = CarbonImmutable::now(config('backups.timezone'));
            $name = "backup-{$timestamp->format('Y-m-d-His')}.backup";
            $path = trim(config('backups.path').'/'.$name, '/');

            $stream = fopen($temporaryPath, 'rb');

            if ($stream === false) {
                throw new RuntimeException('No fue posible leer el respaldo generado.');
            }

            try {
                if (! Storage::disk($this->disk())->put($path, $stream, [
                    'visibility' => 'private',
                    'ContentType' => 'application/octet-stream',
                    'ServerSideEncryption' => 'AES256',
                ])) {
                    throw new RuntimeException('Amazon S3 no confirmó la carga del respaldo.');
                }
            } finally {
                fclose($stream);
            }

            $this->prune();

            return $path;
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    /** @return array<int, array{path: string, name: string, size: int, last_modified: CarbonImmutable}> */
    public function all(): array
    {
        $disk = Storage::disk($this->disk());
        $files = [];

        foreach ($disk->allFiles(config('backups.path')) as $path) {
            $timestamp = $this->timestampFromPath($path);

            if ($timestamp === null) {
                continue;
            }

            $files[] = [
                'path' => $path,
                'name' => basename($path),
                'size' => $disk->size($path),
                'last_modified' => $timestamp,
            ];
        }

        usort($files, fn (array $left, array $right): int => $right['last_modified']->getTimestamp() <=> $left['last_modified']->getTimestamp());

        return $files;
    }

    public function readStream(string $path): mixed
    {
        $this->assertBackupPath($path);

        return Storage::disk($this->disk())->readStream($path);
    }

    public function prune(): void
    {
        $startedAt = microtime(true);
        $disk = Storage::disk($this->disk());
        $now = CarbonImmutable::now(config('backups.timezone'));
        $weekStart = $now->startOfWeek();
        $cutoff = $now->subMonths((int) config('backups.retention.months', 12));
        $latestByWeek = [];
        $deleted = 0;
        $files = $this->all();

        Log::channel('backups')->debug('Limpieza de respaldos iniciada', [
            'files_found' => count($files),
            'retention_months' => config('backups.retention.months', 12),
        ]);

        foreach ($files as $file) {
            $timestamp = $file['last_modified'];

            if ($timestamp->greaterThanOrEqualTo($weekStart)) {
                continue;
            }

            if ($timestamp->greaterThanOrEqualTo($cutoff)) {
                $week = $timestamp->format('o-W');

                if (! isset($latestByWeek[$week])) {
                    $latestByWeek[$week] = $file['path'];

                    continue;
                }
            }

            $disk->delete($file['path']);
            DatabaseBackup::query()->where('path', $file['path'])->delete();
            $deleted++;
        }

        Log::channel('backups')->info('Limpieza de respaldos completada', [
            'files_found' => count($files),
            'files_deleted' => $deleted,
            'duration_ms' => $this->durationInMilliseconds($startedAt),
        ]);
    }

    private function dumpRemoteDatabase(BackupConnection $connection, string $temporaryPath, ?string $keyPath): void
    {
        $startedAt = microtime(true);
        $context = $this->connectionContext($connection);

        Log::channel('backups')->info('Ejecución remota de pg_dump iniciada', $context + [
            'ssh_key_configured' => $keyPath !== null,
        ]);

        $password = $connection->postgres_password === null
            ? ''
            : 'PGPASSWORD='.escapeshellarg($connection->postgres_password).' ';
        $remoteCommand = sprintf(
            '%spg_dump --host=%s --port=%d --username=%s --format=custom --no-owner --no-privileges %s',
            $password,
            escapeshellarg($connection->postgres_host),
            $connection->postgres_port,
            escapeshellarg($connection->postgres_user),
            escapeshellarg($connection->postgres_database),
        );
        $sshCommand = ['ssh', '-p', (string) $connection->ssh_port, '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=accept-new'];

        if ($keyPath !== null) {
            $sshCommand = [...$sshCommand, '-i', $keyPath];
        }

        $sshCommand = [...$sshCommand, "{$connection->ssh_user}@{$connection->ssh_host}", $remoteCommand];
        $output = fopen($temporaryPath, 'wb');

        if ($output === false) {
            throw new RuntimeException('No fue posible abrir el archivo temporal del respaldo.');
        }

        try {
            $process = new Process($sshCommand);
            $process->setTimeout(3600);
            $process->run(function (string $type, string $buffer) use ($output): void {
                if ($type === Process::OUT) {
                    fwrite($output, $buffer);
                }
            });
        } finally {
            fclose($output);
        }

        if (! $process->isSuccessful()) {
            Log::channel('backups')->error('Ejecución remota de pg_dump fallida', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'exit_code' => $process->getExitCode(),
                'error_output' => Str::limit(trim($process->getErrorOutput()), 1000),
            ]);

            throw new RuntimeException('El respaldo remoto falló: '.Str::limit(trim($process->getErrorOutput()), 1000));
        }

        Log::channel('backups')->info('Ejecución remota de pg_dump completada', $context + [
            'duration_ms' => $this->durationInMilliseconds($startedAt),
            'exit_code' => $process->getExitCode(),
            'output_size' => is_file($temporaryPath) ? filesize($temporaryPath) : null,
        ]);
    }

    private function writePrivateKey(BackupConnection $connection): ?string
    {
        if ($connection->ssh_private_key === null || $connection->ssh_private_key === '') {
            return null;
        }

        $path = tempnam(storage_path('app'), 'backup-key-');

        if ($path === false || file_put_contents($path, $connection->ssh_private_key) === false) {
            throw new RuntimeException('No fue posible preparar la llave SSH.');
        }

        chmod($path, 0600);

        return $path;
    }

    private function recordFailure(BackupConnection $connection, \Throwable $exception): void
    {
        DatabaseBackup::query()->create([
            'client_id' => $connection->client_id,
            'project_id' => $connection->project_id,
            'backup_connection_id' => $connection->id,
            'disk' => $this->disk(),
            'path' => '',
            'filename' => '',
            'status' => 'failed',
            'error_message' => Str::limit($exception->getMessage(), 2000),
            'generated_at' => CarbonImmutable::now(config('backups.timezone')),
        ]);
    }

    private function dumpDatabase(string $temporaryPath): void
    {
        $config = config('backups.database');
        $process = new Process([
            config('backups.pg_dump_binary', 'pg_dump'),
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--username='.$config['username'],
            '--format=custom',
            '--no-owner',
            '--no-privileges',
            '--file='.$temporaryPath,
            $config['database'],
        ], null, ['PGPASSWORD' => $config['password']]);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('pg_dump falló: '.Str::limit(trim($process->getErrorOutput()), 1000));
        }
    }

    private function disk(): string
    {
        return (string) config('backups.disk', 's3');
    }

    private function assertBackupPath(string $path): void
    {
        $prefix = trim(config('backups.path'), '/').'/';

        abort_unless(Str::startsWith($path, $prefix) && ! Str::contains($path, ['..', '\\']), 404);
    }

    private function timestampFromPath(string $path): ?CarbonImmutable
    {
        $name = basename($path);
        $timestamp = Str::between($name, 'backup-', '.backup');

        if ($timestamp === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d-His', $timestamp, config('backups.timezone')) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<string, int|string|null> */
    private function connectionContext(BackupConnection $connection): array
    {
        return [
            'backup_connection_id' => $connection->id,
            'client_id' => $connection->client_id,
            'project_id' => $connection->project_id,
            'connection_name' => $connection->name,
            'ssh_host' => $connection->ssh_host,
            'ssh_port' => $connection->ssh_port,
            'ssh_user' => $connection->ssh_user,
            'postgres_host' => $connection->postgres_host,
            'postgres_port' => $connection->postgres_port,
            'postgres_database' => $connection->postgres_database,
            'postgres_user' => $connection->postgres_user,
        ];
    }

    private function durationInMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
