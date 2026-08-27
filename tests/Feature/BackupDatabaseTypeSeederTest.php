<?php

use App\Models\BackupDatabaseType;
use Database\Seeders\BackupDatabaseTypeSeeder;

it('registers PostgreSQL with its backup command', function () {
    $this->seed(BackupDatabaseTypeSeeder::class);

    $type = BackupDatabaseType::query()->where('key', 'postgresql')->firstOrFail();

    expect($type->name)->toBe('PostgreSQL')
        ->and($type->backup_command)->toBe('pg_dump')
        ->and($type->is_active)->toBeTrue();
});
