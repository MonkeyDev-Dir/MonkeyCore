<?php

use App\Livewire\Backups\BackupsTable;
use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\DatabaseBackup;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Database\Seeders\ClientBackupsSeeder;
use Database\Seeders\ClientSeeder;
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

it('lists processed database backups seeded for the test client', function () {
    $this->seed(ClientSeeder::class);
    $this->seed(ClientBackupsSeeder::class);

    $this->actingAs(User::factory()->create())
        ->get(route('backups.index'))
        ->assertSee('demo-client-backup-week-')
        ->assertSee('Cliente de prueba')
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
