<?php

use App\Livewire\Clients\ClientAnnualBackups;
use App\Livewire\Clients\ClientMonthlyBackups;
use App\Livewire\Clients\ClientWeeklyBackups;
use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\DatabaseBackup;
use App\Models\Project;
use App\Models\User;
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
