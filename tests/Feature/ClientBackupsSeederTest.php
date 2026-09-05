<?php

use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\DatabaseBackup;
use App\Models\User;
use Database\Seeders\ClientSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FileTypeSeeder;
use Database\Seeders\UserSeeder;

it('seeds only configured clients and no demo backups', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Client::query()->count())->toBe(3)
        ->and(Client::query()->where('name', 'Cliente de prueba')->exists())->toBeFalse()
        ->and(Client::query()->where('tax_id', '3101007178')->exists())->toBeTrue();

    expect(DatabaseBackup::query()->where('filename', 'like', 'demo-client-backup-%')->exists())->toBeFalse();
});

it('removes the legacy test client and its backups', function () {
    $this->seed(FileTypeSeeder::class);

    $client = Client::factory()->create(['code' => '512122']);
    $connection = BackupConnection::factory()->for($client)->create();
    DatabaseBackup::factory()->for($client)->for($connection, 'backupConnection')->create([
        'filename' => 'demo-client-backup-legacy.backup',
    ]);

    $this->seed(ClientSeeder::class);

    expect(Client::withTrashed()->where('code', '512122')->exists())->toBeFalse()
        ->and(DatabaseBackup::query()->where('filename', 'demo-client-backup-legacy.backup')->exists())->toBeFalse();
});

it('keeps only the administrative user when seeded', function () {
    User::factory()->count(3)->create();

    $this->seed(UserSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(User::query()->value('email'))->toBe('me@gilberthrojas.com');
});
