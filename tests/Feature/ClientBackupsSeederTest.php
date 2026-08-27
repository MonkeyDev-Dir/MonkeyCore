<?php

use App\Models\Client;
use App\Models\DatabaseBackup;
use Database\Seeders\ClientBackupsSeeder;
use Database\Seeders\ClientSeeder;
use Database\Seeders\DatabaseSeeder;

it('seeds only the test and ETAI clients', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Client::query()->count())->toBe(2)
        ->and(Client::query()->where('code', '512122')->exists())->toBeTrue()
        ->and(Client::query()->where('tax_id', '3101007178')->exists())->toBeTrue();
});

it('seeds repeatable database-only backups for the profile client', function () {
    $this->seed(ClientSeeder::class);
    $this->seed(ClientBackupsSeeder::class);

    $client = Client::query()->where('code', '512122')->firstOrFail();
    $backupCount = DatabaseBackup::query()->where('client_id', $client->id)->count();

    expect($backupCount)->toBeGreaterThan(50)
        ->and(DatabaseBackup::query()->where('client_id', $client->id)->where('filename', 'like', 'demo-client-backup-%')->count())
        ->toBe($backupCount);

    $this->seed(ClientBackupsSeeder::class);

    expect(DatabaseBackup::query()->where('client_id', $client->id)->count())->toBe($backupCount);
});
