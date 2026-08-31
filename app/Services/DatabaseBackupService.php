<?php

namespace App\Services;

use App\Jobs\ProcessDatabaseBackup;
use App\Models\BackupConnection;
use App\Models\BackupDatabaseType;
use App\Models\Client;
use App\Models\DatabaseBackup;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    private const MaxDisplayedBackups = 200;

    public function __construct(private BackupSettingsService $settingsService) {}

    /** @return Collection<int, BackupDatabaseType> */
    public function availableDatabaseTypes(): Collection
    {
        return BackupDatabaseType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** @return array{completed: int, failed: int, queued: int} */
    public function createAll(): array
    {
        $summary = ['completed' => 0, 'failed' => 0, 'queued' => 0];
        $startedAt = microtime(true);
        $activeConnections = BackupConnection::query()->where('is_active', true)->count();

        Log::channel('backups')->info('Proceso de respaldos iniciado', [
            'active_connections' => $activeConnections,
            'disk' => $this->disk(),
            'path' => config('backups.path'),
        ]);

        BackupConnection::query()
            ->where('is_active', true)
            ->each(function (BackupConnection $connection) use (&$summary): void {
                if (! $this->settingsService->isDue($connection)) {
                    return;
                }

                $this->settingsService->markRun($connection);
                $backup = DatabaseBackup::query()->create([
                    'client_id' => $connection->client_id,
                    'project_id' => $connection->project_id,
                    'backup_connection_id' => $connection->id,
                    'execution_id' => (string) Str::uuid(),
                    'disk' => $this->disk(),
                    'status' => 'queued',
                    'metadata' => ['queued_at' => now()->toIso8601String()],
                ]);

                try {
                    ProcessDatabaseBackup::dispatch($backup->id);
                    $summary['queued']++;
                } catch (\Throwable $exception) {
                    $backup->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                        'error_message' => Str::limit($exception->getMessage(), 2000),
                        'error_output' => Str::limit($exception->getMessage(), 10000),
                        'metadata' => [
                            'exception' => $exception::class,
                            'phase' => 'dispatch',
                        ],
                    ]);
                    $summary['failed']++;

                    Log::channel('backups')->error('Job de respaldo no pudo ser encolado', [
                        'backup_id' => $backup->id,
                        'backup_connection_id' => $connection->id,
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
            'queued' => $summary['queued'],
            'duration_ms' => $this->durationInMilliseconds($startedAt),
        ]);

        return $summary;
    }

    public function queueForConnection(BackupConnection $connection): DatabaseBackup
    {
        $backup = DatabaseBackup::query()->create([
            'client_id' => $connection->client_id,
            'project_id' => $connection->project_id,
            'backup_connection_id' => $connection->id,
            'execution_id' => (string) Str::uuid(),
            'disk' => $this->disk(),
            'status' => 'queued',
            'metadata' => ['queued_at' => now()->toIso8601String()],
        ]);

        try {
            ProcessDatabaseBackup::dispatch($backup->id);
        } catch (\Throwable $exception) {
            $backup->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => Str::limit($exception->getMessage(), 2000),
                'error_output' => Str::limit($exception->getMessage(), 10000),
                'metadata' => [
                    'exception' => $exception::class,
                    'phase' => 'dispatch',
                ],
            ]);

            Log::channel('backups')->error('Job de respaldo no pudo ser encolado', [
                'backup_id' => $backup->id,
                'backup_connection_id' => $connection->id,
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            report($exception);

            throw $exception;
        }

        return $backup;
    }

    public function createForConnection(BackupConnection $connection, ?DatabaseBackup $backup = null): DatabaseBackup
    {
        $startedAt = microtime(true);
        $context = $this->connectionContext($connection);

        Log::channel('backups')->debug('Preparación del respaldo iniciada', $context + [
            'ssh_key_configured' => $connection->ssh_private_key !== null && $connection->ssh_private_key !== '',
            'postgres_password_configured' => $connection->postgres_password !== null && $connection->postgres_password !== '',
            'mysql_password_configured' => $connection->mysql_password !== null && $connection->mysql_password !== '',
        ]);

        $backup ??= DatabaseBackup::query()->create([
            'client_id' => $connection->client_id,
            'project_id' => $connection->project_id,
            'backup_connection_id' => $connection->id,
            'execution_id' => (string) Str::uuid(),
            'disk' => $this->disk(),
            'status' => 'running',
            'started_at' => now(),
        ]);
        $backup->update(['command' => $this->commandDescription($connection)]);
        $temporaryPath = tempnam(storage_path('app'), 'database-backup-');

        if ($temporaryPath === false) {
            throw new RuntimeException('No fue posible crear el archivo temporal del respaldo.');
        }

        $keyPath = null;

        try {
            $keyPath = $this->writePrivateKey($connection);
            $execution = $this->dumpRemoteDatabase($connection, $temporaryPath, $keyPath);

            $timestamp = CarbonImmutable::now(config('backups.timezone'));
            $extension = $connection->database_type === 'mysql' ? 'sql.gz' : 'backup';
            $name = "backup-{$timestamp->format('Y-m-d-His')}.{$extension}";
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

            if (! $disk->exists($path)) {
                throw new RuntimeException('El almacenamiento no confirmó la existencia del respaldo.');
            }

            if ($size !== filesize($temporaryPath)) {
                throw new RuntimeException('El tamaño del respaldo en almacenamiento no coincide con el archivo generado.');
            }

            $checksum = hash_file('sha256', $temporaryPath);

            if ($checksum === false) {
                throw new RuntimeException('No fue posible calcular la huella del respaldo generado.');
            }

            Log::channel('backups')->info('Verificación del respaldo en almacenamiento completada', $context + [
                'disk' => $this->disk(),
                'path' => $path,
                'stored_size' => $size,
                'checksum' => $checksum,
                'checksum_algorithm' => 'sha256',
            ]);

            Log::channel('backups')->info('Carga del respaldo a almacenamiento completada', $context + [
                'disk' => $this->disk(),
                'path' => $path,
                'size' => $size,
            ]);

            $backup->update([
                'disk' => $this->disk(),
                'path' => $path,
                'filename' => $name,
                'size' => $size,
                'status' => 'completed',
                'exit_code' => $execution['exit_code'],
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'checksum' => $checksum,
                'started_at' => $backup->started_at ?? $timestamp,
                'completed_at' => now(),
                'storage_verified_at' => now(),
                'generated_at' => $timestamp,
                'metadata' => [
                    'remote_duration_ms' => $execution['duration_ms'],
                    'output_size' => $execution['output_size'],
                    'stored_size' => $size,
                    'storage_exists' => true,
                    'checksum_algorithm' => 'sha256',
                ],
            ]);
            $backup->refresh();

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

    public function testConnection(BackupConnection $connection): void
    {
        $databaseType = $connection->databaseType;

        if ($databaseType === null || ! $databaseType->is_active) {
            throw new RuntimeException('El tipo de base de datos no está disponible para comprobar la conexión.');
        }

        $keyPath = null;

        try {
            $keyPath = $this->writePrivateKey($connection);
            $process = new Process($this->buildSshCommand($connection, $this->buildRemoteDumpCommand($connection, $databaseType, true), $keyPath));
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('La conexión falló: '.Str::limit(trim($process->getErrorOutput()), 1000));
            }

            Log::channel('backups')->info('Conexión de respaldo comprobada', $this->connectionContext($connection));
        } finally {
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
    public function all(?int $limit = null): array
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
                'last_modified' => $timestamp,
            ];
        }

        usort($files, fn (array $left, array $right): int => $right['last_modified']->getTimestamp() <=> $left['last_modified']->getTimestamp());

        return collect($files)
            ->when($limit !== null, fn (Collection $files): Collection => $files->take($limit))
            ->map(fn (array $file): array => [
                ...$file,
                'size' => $disk->size($file['path']),
            ])
            ->values()
            ->all();
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function paginateProcessed(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        $query = DatabaseBackup::query()
            ->with(['client', 'project', 'backupConnection'])
            ->where('status', 'completed')
            ->whereNotNull('generated_at')
            ->when(trim($search) !== '', function (Builder $query) use ($search): void {
                $term = '%'.Str::lower(Str::ascii(trim($search))).'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->whereRaw('LOWER(filename) LIKE ?', [$term])
                        ->orWhereHas('client', fn (Builder $query): Builder => $query->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(code) LIKE ?', [$term]))
                        ->orWhereHas('project', fn (Builder $query): Builder => $query->whereRaw('LOWER(name) LIKE ?', [$term]))
                        ->orWhereHas('backupConnection', fn (Builder $query): Builder => $query->whereRaw('LOWER(name) LIKE ?', [$term]));
                });
            })
            ->orderByDesc('generated_at');

        if (DatabaseBackup::query()->where('status', 'completed')->exists()) {
            $total = min(
                $query->clone()
                    ->limit(self::MaxDisplayedBackups + 1)
                    ->toBase()
                    ->get(['id'])
                    ->count(),
                self::MaxDisplayedBackups,
            );

            return $query
                ->limit(self::MaxDisplayedBackups)
                ->paginate($perPage, ['*'], 'page', null, $total)
                ->through(fn (DatabaseBackup $backup): array => [
                    'id' => $backup->id,
                    'name' => $backup->filename,
                    'path' => $backup->path,
                    'size' => $backup->size ?? 0,
                    'last_modified' => $backup->generated_at,
                    'client_code' => $backup->client?->code,
                    'client_name' => $backup->client?->name,
                    'project_name' => $backup->project?->name,
                    'database_type' => $backup->backupConnection?->database_type ?? 'postgresql',
                    'extension' => pathinfo((string) $backup->filename, PATHINFO_EXTENSION),
                ]);
        }

        $files = collect($this->all(self::MaxDisplayedBackups))
            ->when(trim($search) !== '', fn (Collection $files): Collection => $files->filter(
                fn (array $file): bool => Str::contains(
                    Str::lower(Str::ascii($file['name'])),
                    Str::lower(Str::ascii(trim($search))),
                ),
            ))
            ->map(fn (array $file): array => [
                ...$file,
                'id' => null,
                'client_code' => null,
                'client_name' => null,
                'project_name' => null,
                'database_type' => 'postgresql',
                'extension' => pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION),
            ])
            ->values();
        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $files->forPage($page, $perPage)->values(),
            $files->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );
    }

    public function readStream(string $path): mixed
    {
        $this->assertBackupPath($path);

        return Storage::disk($this->disk())->readStream($path);
    }

    /** @return Collection<int, array{date: CarbonImmutable, backups: Collection<int, DatabaseBackup>}> */
    public function forClientCurrentWeek(Client $client, ?int $projectId = null): Collection
    {
        $now = $this->now();
        $backups = $this->completedBackupsForClient($client, $projectId)
            ->whereBetween('generated_at', [$now->startOfWeek(), $now->endOfWeek()])
            ->orderByDesc('generated_at')
            ->get();

        return $backups
            ->groupBy(fn (DatabaseBackup $backup): string => $this->localDate($backup)->format('Y-m-d'))
            ->map(fn (Collection $dayBackups): Collection => $dayBackups->take(5)->values())
            ->map(fn (Collection $dayBackups, string $date): array => [
                'date' => CarbonImmutable::createFromFormat('Y-m-d', $date, $this->timezone()),
                'backups' => $dayBackups,
            ])
            ->values();
    }

    /** @return Collection<int, array{month: CarbonImmutable, backups: Collection<int, DatabaseBackup>}> */
    public function forClientMonthlyHistory(Client $client, ?int $projectId = null): Collection
    {
        $now = $this->now();
        $backups = $this->completedBackupsForClient($client, $projectId)
            ->whereBetween('generated_at', [$now->subMonths(11)->startOfMonth(), $now->endOfMonth()])
            ->orderByDesc('generated_at')
            ->get()
            ->reject(fn (DatabaseBackup $backup): bool => $this->localDate($backup)->greaterThanOrEqualTo($now->startOfWeek()))
            ->groupBy(fn (DatabaseBackup $backup): string => "{$this->localDate($backup)->format('o-W')}:{$backup->backup_connection_id}")
            ->map(fn (Collection $weekBackups): DatabaseBackup => $weekBackups->first());

        return $backups
            ->groupBy(fn (DatabaseBackup $backup): string => $this->localDate($backup)->format('Y-m'))
            ->map(fn (Collection $monthBackups, string $month): array => [
                'month' => CarbonImmutable::createFromFormat('Y-m-d', "{$month}-01", $this->timezone()),
                'backups' => $monthBackups->sortByDesc('generated_at')->values(),
            ])
            ->sortByDesc('month')
            ->values();
    }

    /** @return Collection<int, array{year: CarbonImmutable, backups: Collection<int, DatabaseBackup>}> */
    public function forClientAnnualHistory(Client $client, ?int $projectId = null): Collection
    {
        $now = $this->now();
        $monthlyStart = $now->subMonths(11)->startOfMonth();
        $settings = $this->settingsService->current();
        $backups = $this->completedBackupsForClient($client, $projectId)
            ->whereBetween('generated_at', [$now->subYears($settings->monthly_retention_years)->startOfMonth(), $monthlyStart->subSecond()])
            ->orderByDesc('generated_at')
            ->get()
            ->groupBy(fn (DatabaseBackup $backup): string => "{$this->localDate($backup)->format('Y-m')}:{$backup->backup_connection_id}")
            ->map(fn (Collection $monthBackups): DatabaseBackup => $monthBackups->first());

        return $backups
            ->groupBy(fn (DatabaseBackup $backup): string => $this->localDate($backup)->format('Y'))
            ->map(fn (Collection $yearBackups, string $year): array => [
                'year' => CarbonImmutable::createFromFormat('Y-m-d', "{$year}-01-01", $this->timezone()),
                'backups' => $yearBackups->sortByDesc('generated_at')->values(),
            ])
            ->sortByDesc('year')
            ->values();
    }

    public function readStreamForClient(Client $client, DatabaseBackup $backup): mixed
    {
        abort_unless($backup->client_id === $client->id && $backup->status === 'completed', 404);

        return $this->readStream($backup->path);
    }

    public function prune(): void
    {
        $startedAt = microtime(true);
        $disk = Storage::disk($this->disk());
        $now = $this->now();
        $latestByMonth = [];
        $deleted = 0;
        $files = DatabaseBackup::query()
            ->where('status', 'completed')
            ->whereNotNull('generated_at')
            ->with('backupConnection')
            ->orderByDesc('generated_at')
            ->get();

        Log::channel('backups')->debug('Limpieza de respaldos iniciada', [
            'files_found' => count($files),
            'retention_scope' => 'per_connection',
        ]);

        foreach ($files as $file) {
            $timestamp = $file->generated_at->setTimezone($this->timezone());

            $retentionKey = "{$file->client_id}:{$file->backup_connection_id}";
            $settings = $this->settingsService->forConnection($file->backupConnection);
            $monthlyCutoff = $now->subMonths($settings['daily_retention_months']);
            $annualCutoff = $now->subYears($settings['monthly_retention_years']);

            if ($timestamp->greaterThanOrEqualTo($monthlyCutoff)) {
                continue;
            }

            if ($timestamp->greaterThanOrEqualTo($annualCutoff)) {
                $month = "{$retentionKey}:{$timestamp->format('Y-m')}";

                if (! isset($latestByMonth[$month])) {
                    $latestByMonth[$month] = $file->id;

                    continue;
                }
            }

            $disk->delete($file->path);
            $file->delete();
            $deleted++;
        }

        Log::channel('backups')->info('Limpieza de respaldos completada', [
            'files_found' => count($files),
            'files_deleted' => $deleted,
            'duration_ms' => $this->durationInMilliseconds($startedAt),
        ]);
    }

    /** @return array{exit_code: int|null, duration_ms: int, output_size: int|false} */
    private function dumpRemoteDatabase(BackupConnection $connection, string $temporaryPath, ?string $keyPath): array
    {
        $startedAt = microtime(true);
        $context = $this->connectionContext($connection);
        $databaseType = $connection->databaseType;

        if ($databaseType === null || ! $databaseType->is_active) {
            throw new RuntimeException('El tipo de base de datos no está disponible para respaldos.');
        }

        Log::channel('backups')->info('Ejecución remota de respaldo iniciada', $context + [
            'ssh_key_configured' => $keyPath !== null,
            'backup_command' => $databaseType->backup_command,
        ]);

        $remoteCommand = $this->buildRemoteDumpCommand($connection, $databaseType);
        $sshCommand = $this->buildSshCommand($connection, $remoteCommand, $keyPath);
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
            Log::channel('backups')->error('Ejecución remota de respaldo fallida', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'exit_code' => $process->getExitCode(),
                'error_output' => Str::limit(trim($process->getErrorOutput()), 1000),
            ]);

            throw new RuntimeException('El respaldo remoto falló: '.Str::limit(trim($process->getErrorOutput()), 1000));
        }

        Log::channel('backups')->info('Ejecución remota de respaldo completada', $context + [
            'duration_ms' => $this->durationInMilliseconds($startedAt),
            'exit_code' => $process->getExitCode(),
            'output_size' => is_file($temporaryPath) ? filesize($temporaryPath) : null,
        ]);

        return [
            'exit_code' => $process->getExitCode(),
            'duration_ms' => $this->durationInMilliseconds($startedAt),
            'output_size' => is_file($temporaryPath) ? filesize($temporaryPath) : false,
        ];
    }

    private function commandDescription(BackupConnection $connection): string
    {
        $databaseType = $connection->databaseType;

        return sprintf(
            '%s --host=%s --port=%d --user=%s --database=%s',
            $databaseType?->backup_command ?? $connection->database_type,
            $connection->database_type === 'mysql' ? $connection->mysql_host : $connection->postgres_host,
            $connection->database_type === 'mysql' ? $connection->mysql_port : $connection->postgres_port,
            $connection->database_type === 'mysql' ? $connection->mysql_user : $connection->postgres_user,
            $connection->database_type === 'mysql' ? $connection->mysql_database : $connection->postgres_database,
        );
    }

    private function buildRemoteDumpCommand(BackupConnection $connection, BackupDatabaseType $databaseType, bool $checkOnly = false): string
    {
        if ($databaseType->key === 'mysql') {
            $password = $connection->mysql_password === null
                ? ''
                : 'MYSQL_PWD='.escapeshellarg($connection->mysql_password).' ';
            $options = $checkOnly
                ? '--no-data --skip-comments --compact'
                : '--single-transaction --routines --triggers --events';

            return sprintf(
                '%s%s --host=%s --port=%d --user=%s %s --databases %s%s',
                $password,
                escapeshellarg($databaseType->backup_command),
                escapeshellarg($connection->mysql_host),
                $connection->mysql_port,
                escapeshellarg($connection->mysql_user),
                $options,
                escapeshellarg($connection->mysql_database),
                $checkOnly ? ' > /dev/null' : ' | gzip',
            );
        }

        $password = $connection->postgres_password === null
            ? ''
            : 'PGPASSWORD='.escapeshellarg($connection->postgres_password).' ';
        $options = $checkOnly
            ? '--schema-only --no-owner --no-privileges'
            : '--format=custom --no-owner --no-privileges';

        return sprintf(
            '%s%s --host=%s --port=%d --username=%s %s %s%s',
            $password,
            escapeshellarg($databaseType->backup_command),
            escapeshellarg($connection->postgres_host),
            $connection->postgres_port,
            escapeshellarg($connection->postgres_user),
            $options,
            escapeshellarg($connection->postgres_database),
            $checkOnly ? ' > /dev/null' : '',
        );
    }

    /** @return array<int, string> */
    private function buildSshCommand(BackupConnection $connection, string $remoteCommand, ?string $keyPath): array
    {
        $sshCommand = ['ssh', '-p', (string) $connection->ssh_port, '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=accept-new'];

        if ($keyPath !== null) {
            $sshCommand = [...$sshCommand, '-i', $keyPath];
        }

        return [...$sshCommand, "{$connection->ssh_user}@{$connection->ssh_host}", $remoteCommand];
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

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    private function timezone(): string
    {
        return (string) config('backups.timezone');
    }

    private function localDate(DatabaseBackup $backup): CarbonImmutable
    {
        return $backup->generated_at->setTimezone($this->timezone());
    }

    private function completedBackupsForClient(Client $client, ?int $projectId = null): Builder
    {
        return DatabaseBackup::query()
            ->with(['backupConnection', 'project'])
            ->where('client_id', $client->id)
            ->where('status', 'completed')
            ->whereNotNull('generated_at')
            ->when($projectId !== null, fn (Builder $query): Builder => $query->where('project_id', $projectId));
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
            'mysql_host' => $connection->mysql_host,
            'mysql_port' => $connection->mysql_port,
            'mysql_database' => $connection->mysql_database,
            'mysql_user' => $connection->mysql_user,
        ];
    }

    private function durationInMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
