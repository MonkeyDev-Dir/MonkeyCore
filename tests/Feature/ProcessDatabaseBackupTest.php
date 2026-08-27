<?php

use App\Jobs\ProcessDatabaseBackup;
use App\Models\BackupConnection;
use App\Models\DatabaseBackup;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('creates a database record and queues one job per active connection', function () {
    Storage::fake('s3');
    Queue::fake();
    $connection = BackupConnection::factory()->create();

    $summary = app(DatabaseBackupService::class)->createAll();
    $backup = DatabaseBackup::query()->firstOrFail();

    expect($summary)->toMatchArray(['completed' => 0, 'failed' => 0, 'queued' => 1])
        ->and($backup->status)->toBe('queued')
        ->and($backup->execution_id)->not->toBeNull()
        ->and($backup->error_message)->toBeNull();

    Queue::assertPushed(ProcessDatabaseBackup::class, fn (ProcessDatabaseBackup $job): bool => $job->backupId === $backup->id);
    expect($connection->id)->toBe($backup->backup_connection_id);
});

it('records execution time and process error when a job fails', function () {
    $backup = DatabaseBackup::factory()->create(['status' => 'queued', 'attempts' => 0]);
    $exception = new RuntimeException('ssh: connection refused');
    $service = Mockery::mock(DatabaseBackupService::class);
    $service->shouldReceive('createForConnection')
        ->once()
        ->andThrow($exception);

    try {
        (new ProcessDatabaseBackup($backup->id))->handle($service);
    } catch (RuntimeException $caught) {
        expect($caught)->toBe($exception);
    }

    $backup->refresh();

    expect($backup->status)->toBe('failed')
        ->and($backup->attempts)->toBe(1)
        ->and($backup->duration_ms)->toBeGreaterThanOrEqual(0)
        ->and($backup->error_message)->toBe('ssh: connection refused')
        ->and($backup->error_output)->toContain('ssh: connection refused')
        ->and($backup->completed_at)->not->toBeNull()
        ->and($backup->metadata['exception'])->toBe(RuntimeException::class);
});
