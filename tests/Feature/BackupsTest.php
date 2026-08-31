<?php

use App\Livewire\Backups\BackupsTable;
use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\DatabaseBackup;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

it('requires authentication to view database backups', function () {
    $this->get(route('backups.index'))
        ->assertRedirectToRoute('login');
});

it('lists private database backups stored in s3', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('database-backups/backup-2026-08-26-050000.backup', 'backup contents');
    Storage::disk('s3')->put('database-backups/ignore.txt', 'not a backup');

    $this->actingAs(User::factory()->create())
        ->get(route('backups.index'))
        ->assertOk()
        ->assertViewIs('pages.backups')
        ->assertSee('backup-2026-08-26-050000.backup')
        ->assertDontSee('ignore.txt');
});

it('lists processed database backups for a client', function () {
    $client = Client::factory()->create(['name' => 'Cliente respaldado']);
    $connection = BackupConnection::factory()->for($client)->create();
    DatabaseBackup::factory()->for($client)->for($connection, 'backupConnection')->create([
        'filename' => 'backup-processed.backup',
        'status' => 'completed',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('backups.index'))
        ->assertSee('backup-processed.backup')
        ->assertSee('Cliente respaldado')
        ->assertSee('PostgreSQL')
        ->assertSee('.backup')
        ->assertDontSee('Procesado');
});

it('searches processed backups by filename or client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['name' => 'Cliente buscable']);
    DatabaseBackup::factory()->create(['client_id' => $client->id, 'filename' => 'backup-visible.backup', 'status' => 'completed']);
    DatabaseBackup::factory()->create(['filename' => 'backup-hidden.backup', 'status' => 'completed']);

    Livewire::actingAs($user)
        ->test(BackupsTable::class)
        ->set('search', 'Cliente buscable')
        ->assertSee('backup-visible.backup')
        ->assertDontSee('backup-hidden.backup');
});

it('limits the processed backup listing to the 200 most recent results', function () {
    $client = Client::factory()->create();
    $connection = BackupConnection::factory()->for($client)->create();

    DatabaseBackup::factory()
        ->count(201)
        ->sequence(fn (Sequence $sequence): array => [
            'client_id' => $client->id,
            'backup_connection_id' => $connection->id,
            'filename' => "backup-{$sequence->index}.backup",
            'generated_at' => Carbon::now()->subMinutes($sequence->index),
        ])
        ->create();

    $backups = app(DatabaseBackupService::class)->paginateProcessed();

    expect($backups->total())->toBe(200)
        ->and($backups->first()['name'])->toBe('backup-0.backup');
});

it('downloads a backup from s3', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('database-backups/backup-2026-08-26-050000.backup', 'backup contents');

    $this->actingAs(User::factory()->create())
        ->get(route('backups.download', ['path' => 'database-backups/backup-2026-08-26-050000.backup']))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=backup-2026-08-26-050000.backup')
        ->assertStreamedContent('backup contents');
});

it('does not allow downloading a path outside the backup directory', function () {
    Storage::fake('s3');

    $this->actingAs(User::factory()->create())
        ->get(route('backups.download', ['path' => 'other/file.backup']))
        ->assertNotFound();
});

it('retains recent backups and the latest monthly backup until the configured limit', function () {
    Storage::fake('s3');
    $this->travelTo('2026-08-31 12:00:00');
    $client = Client::factory()->create();
    $connection = BackupConnection::factory()->for($client)->create();

    $recent = DatabaseBackup::factory()->for($client)->for($connection, 'backupConnection')->create([
        'path' => 'database-backups/recent.backup',
        'generated_at' => now()->subDays(10),
    ]);
    $monthly = DatabaseBackup::factory()->for($client)->for($connection, 'backupConnection')->create([
        'path' => 'database-backups/monthly.backup',
        'generated_at' => now()->subMonths(2)->startOfMonth()->addDays(20),
    ]);
    $duplicate = DatabaseBackup::factory()->for($client)->for($connection, 'backupConnection')->create([
        'path' => 'database-backups/duplicate.backup',
        'generated_at' => now()->subMonths(2)->startOfMonth()->addDays(5),
    ]);
    $expired = DatabaseBackup::factory()->for($client)->for($connection, 'backupConnection')->create([
        'path' => 'database-backups/expired.backup',
        'generated_at' => now()->subYears(4),
    ]);
    Storage::disk('s3')->put($recent->path, 'recent');
    Storage::disk('s3')->put($monthly->path, 'monthly');
    Storage::disk('s3')->put($duplicate->path, 'duplicate');
    Storage::disk('s3')->put($expired->path, 'expired');

    app(DatabaseBackupService::class)->prune();

    expect(DatabaseBackup::query()->pluck('id')->all())
        ->toBe([$recent->id, $monthly->id]);
    Storage::disk('s3')->assertMissing($duplicate->path);
    Storage::disk('s3')->assertMissing($expired->path);
});
