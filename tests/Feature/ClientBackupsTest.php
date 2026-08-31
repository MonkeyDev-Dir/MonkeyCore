<?php

use App\Jobs\ProcessDatabaseBackup;
use App\Livewire\Clients\BackupConnectionModal;
use App\Livewire\Clients\BackupScheduleModal;
use App\Livewire\Clients\ClientAnnualBackups;
use App\Livewire\Clients\ClientBackups;
use App\Livewire\Clients\ClientMonthlyBackups;
use App\Livewire\Clients\ClientWeeklyBackups;
use App\Models\BackupConnection;
use App\Models\BackupDatabaseType;
use App\Models\Client;
use App\Models\DatabaseBackup;
use App\Models\Project;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('shows all client backup components on the dedicated backups page', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->get(route('clients.backups', ['clientCode' => $client->code]))
        ->assertOk()
        ->assertViewIs('pages.client-backups')
        ->assertSee('client-weekly-backups')
        ->assertSee('client-monthly-backups')
        ->assertSee('client-annual-backups');
});

it('groups the current client backups by day', function () {
    $client = Client::factory()->create();
    $connection = BackupConnection::factory()->create(['client_id' => $client->id]);

    DatabaseBackup::factory()->createMany([
        ['client_id' => $client->id, 'backup_connection_id' => $connection->id, 'filename' => 'monday.backup', 'generated_at' => '2026-08-24 08:00:00'],
        ['client_id' => $client->id, 'backup_connection_id' => $connection->id, 'filename' => 'thursday.backup', 'generated_at' => '2026-08-27 08:00:00'],
    ]);

    Livewire::test(ClientWeeklyBackups::class, ['clientCode' => $client->code])
        ->assertSee('24/08/2026')
        ->assertSee('27/08/2026')
        ->assertSee('monday.backup')
        ->assertSee('thursday.backup')
        ->assertSee('<table', false);
});

it('filters client backups by project', function () {
    $client = Client::factory()->create();
    $firstProject = Project::factory()->create(['client_id' => $client->id, 'name' => 'Proyecto uno']);
    $secondProject = Project::factory()->create(['client_id' => $client->id, 'name' => 'Proyecto dos']);
    $firstConnection = BackupConnection::factory()->create(['client_id' => $client->id, 'project_id' => $firstProject->id]);
    $secondConnection = BackupConnection::factory()->create(['client_id' => $client->id, 'project_id' => $secondProject->id]);

    DatabaseBackup::factory()->createMany([
        ['client_id' => $client->id, 'project_id' => $firstProject->id, 'backup_connection_id' => $firstConnection->id, 'filename' => 'project-one.backup', 'generated_at' => '2026-08-27 08:00:00'],
        ['client_id' => $client->id, 'project_id' => $secondProject->id, 'backup_connection_id' => $secondConnection->id, 'filename' => 'project-two.backup', 'generated_at' => '2026-08-27 09:00:00'],
    ]);

    Livewire::test(ClientWeeklyBackups::class, ['clientCode' => $client->code, 'projectId' => $firstProject->id])
        ->assertSee('project-one.backup')
        ->assertDontSee('project-two.backup');
});

it('creates a backup connection for the selected client and project', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    BackupDatabaseType::factory()->create(['key' => 'postgresql', 'name' => 'PostgreSQL', 'backup_command' => 'pg_dump']);

    Livewire::test(BackupConnectionModal::class)
        ->call('openCreate', $client->code)
        ->fill([
            'name' => 'Producción',
            'projectId' => $project->id,
            'sshHost' => '10.0.0.10',
            'sshUser' => 'forge',
            'postgresDatabase' => 'app',
            'postgresUser' => 'postgres',
            'postgresPassword' => 'secret',
        ])
        ->call('save')
        ->assertSet('isOpen', false);

    $connection = BackupConnection::query()->where('name', 'Producción')->firstOrFail();

    expect($connection->client_id)->toBe($client->id)
        ->and($connection->project_id)->toBe($project->id)
        ->and($connection->postgres_password)->toBe('secret');
});

it('does not allow assigning a project from another client to a backup connection', function () {
    $client = Client::factory()->create();
    $otherProject = Project::factory()->create();

    Livewire::test(BackupConnectionModal::class)
        ->call('openCreate', $client->code)
        ->fill([
            'name' => 'Producción',
            'projectId' => $otherProject->id,
            'sshHost' => '10.0.0.10',
            'sshUser' => 'forge',
            'postgresDatabase' => 'app',
            'postgresUser' => 'postgres',
        ])
        ->call('save')
        ->assertHasErrors(['projectId']);
});

it('saves a custom schedule for an individual backup connection', function () {
    $client = Client::factory()->create();
    $connection = BackupConnection::factory()->for($client)->create();

    Livewire::test(BackupScheduleModal::class)
        ->call('open', $client->code, $connection->id)
        ->fill([
            'useCustomSchedule' => true,
            'frequency' => 'weekly',
            'dailyRetentionMonths' => 2,
            'monthlyRetentionYears' => 5,
        ])
        ->call('save');

    $connection->refresh();

    expect($connection->backup_frequency)->toBe('weekly')
        ->and($connection->backup_daily_retention_months)->toBe(2)
        ->and($connection->backup_monthly_retention_years)->toBe(5);
});

it('creates a MySQL backup connection using its database fields', function () {
    $client = Client::factory()->create();
    BackupDatabaseType::factory()->create(['key' => 'mysql', 'name' => 'MySQL', 'backup_command' => 'mysqldump']);

    Livewire::test(BackupConnectionModal::class)
        ->call('openCreate', $client->code)
        ->set('databaseType', 'mysql')
        ->fill([
            'name' => 'MySQL producción',
            'sshHost' => '204.48.27.226',
            'sshUser' => 'forge',
            'mysqlDatabase' => 'mi_base',
            'mysqlUser' => 'mi_usuario',
            'mysqlPassword' => 'secret',
        ])
        ->call('save')
        ->assertSet('isOpen', false);

    $connection = BackupConnection::query()->where('name', 'MySQL producción')->firstOrFail();

    expect($connection->database_type)->toBe('mysql')
        ->and($connection->mysql_database)->toBe('mi_base')
        ->and($connection->mysql_password)->toBe('secret');
});

it('opens a backup connection for editing and preserves omitted secrets', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    BackupDatabaseType::factory()->create(['key' => 'postgresql', 'name' => 'PostgreSQL', 'backup_command' => 'pg_dump']);
    $connection = BackupConnection::factory()->create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'name' => 'Producción',
        'postgres_password' => 'secret',
    ]);

    Livewire::test(BackupConnectionModal::class)
        ->call('openEdit', $client->code, $connection->id)
        ->assertSet('connectionId', $connection->id)
        ->assertSet('name', 'Producción')
        ->assertSet('postgresPassword', '')
        ->assertSee('Configuración de respaldo')
        ->assertDontSee('Nueva configuración de respaldo')
        ->call('save')
        ->assertSet('isOpen', false);

    expect($connection->refresh()->postgres_password)->toBe('secret');
});

it('checks a backup connection without creating a backup', function () {
    $client = Client::factory()->create();
    $databaseType = BackupDatabaseType::factory()->create(['key' => 'postgresql', 'name' => 'PostgreSQL', 'backup_command' => 'pg_dump']);
    $backupService = Mockery::mock(DatabaseBackupService::class);
    $backupService->shouldReceive('availableDatabaseTypes')->once()->andReturn(collect([$databaseType]));
    $backupService->shouldReceive('testConnection')->once()->andReturnUsing(function (BackupConnection $connection): void {
        expect($connection->name)->toBe('Producción');
    });
    $this->app->instance(DatabaseBackupService::class, $backupService);

    Livewire::test(BackupConnectionModal::class)
        ->call('openCreate', $client->code)
        ->fill([
            'name' => 'Producción',
            'sshHost' => '10.0.0.10',
            'sshUser' => 'forge',
            'postgresDatabase' => 'app',
            'postgresUser' => 'postgres',
            'postgresPassword' => 'secret',
        ])
        ->call('testConnection')
        ->assertHasNoErrors()
        ->assertDispatched('backup-connection-tested');

    expect(BackupConnection::query()->where('name', 'Producción')->exists())->toBeFalse();
});

it('renders backup connections as edit actions', function () {
    $client = Client::factory()->create();
    $connection = BackupConnection::factory()->create(['client_id' => $client->id]);

    Livewire::test(ClientBackups::class, ['clientCode' => $client->code])
        ->assertSee('open-backup-connection-edit', false)
        ->assertSee((string) $connection->id, false);
});

it('queues a manual backup for a client connection', function () {
    Queue::fake();
    $client = Client::factory()->create();
    $connection = BackupConnection::factory()->create(['client_id' => $client->id]);

    Livewire::test(ClientBackups::class, ['clientCode' => $client->code])
        ->call('queueBackup', $connection->id)
        ->assertDispatched('backup-queued');

    $backup = DatabaseBackup::query()->firstOrFail();

    expect($backup->backup_connection_id)->toBe($connection->id)
        ->and($backup->status)->toBe('queued');

    Queue::assertPushed(ProcessDatabaseBackup::class, fn (ProcessDatabaseBackup $job): bool => $job->backupId === $backup->id);
});

it('does not queue a second manual backup while one is in progress', function () {
    Queue::fake();
    $client = Client::factory()->create();
    $connection = BackupConnection::factory()->create(['client_id' => $client->id]);
    DatabaseBackup::factory()->create([
        'client_id' => $client->id,
        'backup_connection_id' => $connection->id,
        'status' => 'running',
    ]);

    Livewire::test(ClientBackups::class, ['clientCode' => $client->code])
        ->call('queueBackup', $connection->id)
        ->assertDispatched('backup-queue-warning');

    expect(DatabaseBackup::query()->count())->toBe(1);
    Queue::assertNothingPushed();
});

it('shows only the five latest backups for each day', function () {
    $client = Client::factory()->create();
    $connection = BackupConnection::factory()->create(['client_id' => $client->id]);

    DatabaseBackup::factory()->createMany(
        collect(range(1, 6))->map(fn (int $backupNumber): array => [
            'client_id' => $client->id,
            'backup_connection_id' => $connection->id,
            'filename' => "backup-{$backupNumber}.backup",
            'generated_at' => sprintf('2026-08-27 %02d:00:00', $backupNumber + 7),
        ])->all(),
    );

    Livewire::test(ClientWeeklyBackups::class, ['clientCode' => $client->code])
        ->assertSee('backup-6.backup')
        ->assertSee('backup-2.backup')
        ->assertDontSee('backup-1.backup');
});

it('keeps the latest backup of each week in the monthly history', function () {
    $client = Client::factory()->create();
    $connection = BackupConnection::factory()->create(['client_id' => $client->id]);

    DatabaseBackup::factory()->createMany([
        ['client_id' => $client->id, 'backup_connection_id' => $connection->id, 'filename' => 'older-week.backup', 'generated_at' => '2026-08-18 08:00:00'],
        ['client_id' => $client->id, 'backup_connection_id' => $connection->id, 'filename' => 'latest-week.backup', 'generated_at' => '2026-08-20 08:00:00'],
    ]);

    Livewire::test(ClientMonthlyBackups::class, ['clientCode' => $client->code])
        ->assertSee('latest-week.backup')
        ->assertSee('tallstackui_accordion', false)
        ->assertDontSee('older-week.backup');
});

it('keeps the latest backup of each month in the annual history', function () {
    $client = Client::factory()->create();
    $connection = BackupConnection::factory()->create(['client_id' => $client->id]);

    DatabaseBackup::factory()->createMany([
        ['client_id' => $client->id, 'backup_connection_id' => $connection->id, 'filename' => 'older-month.backup', 'generated_at' => '2025-07-10 08:00:00'],
        ['client_id' => $client->id, 'backup_connection_id' => $connection->id, 'filename' => 'latest-month.backup', 'generated_at' => '2025-07-20 08:00:00'],
    ]);

    Livewire::test(ClientAnnualBackups::class, ['clientCode' => $client->code])
        ->assertSee('latest-month.backup')
        ->assertSee('<table', false)
        ->assertDontSee('older-month.backup');
});

it('does not allow a client to download another client backup', function () {
    Storage::fake('s3');
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $connection = BackupConnection::factory()->create(['client_id' => $otherClient->id]);
    $backup = DatabaseBackup::factory()->create([
        'client_id' => $otherClient->id,
        'backup_connection_id' => $connection->id,
        'path' => 'database-backups/'.$otherClient->id.'/backup.backup',
        'filename' => 'backup.backup',
    ]);

    Storage::disk('s3')->put($backup->path, 'backup contents');

    $this->actingAs(User::factory()->create())
        ->get(route('clients.backups.download', ['clientCode' => $client->code, 'backup' => $backup->id]))
        ->assertNotFound();
});
