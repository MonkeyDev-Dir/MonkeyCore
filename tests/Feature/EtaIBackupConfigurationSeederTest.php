<?php

use App\Models\BackupConnection;
use App\Models\Client;
use Database\Seeders\EtaIBackupConfigurationSeeder;

it('seeds the ETAI backup connection without a project', function () {
    $this->seed(EtaIBackupConfigurationSeeder::class);

    $client = Client::query()->where('tax_id', '3101007178')->firstOrFail();
    $connection = BackupConnection::query()->where('client_id', $client->id)->firstOrFail();

    expect($client->name)->toBe('Instituto Agropecuario Costarricense S.A.')
        ->and($connection->name)->toBe('Metai')
        ->and($connection->project_id)->toBeNull()
        ->and($connection->ssh_host)->toBe('204.48.27.226')
        ->and($connection->postgres_host)->toBe('localhost')
        ->and($connection->postgres_database)->toBe('etai_iacsa_pg')
        ->and($connection->postgres_user)->toBe('forge');
});

it('keeps connection secrets encrypted in the database', function () {
    $this->seed(EtaIBackupConfigurationSeeder::class);

    $connection = BackupConnection::query()->where('name', 'Metai')->firstOrFail();
    $connection->update([
        'postgres_password' => 'secret-password',
        'ssh_private_key' => 'private-key',
    ]);

    $raw = $connection->getRawOriginal();

    expect($connection->refresh()->postgres_password)->toBe('secret-password')
        ->and($connection->ssh_private_key)->toBe('private-key')
        ->and($raw['postgres_password'])->not->toBe('secret-password')
        ->and($raw['ssh_private_key'])->not->toBe('private-key');
});
