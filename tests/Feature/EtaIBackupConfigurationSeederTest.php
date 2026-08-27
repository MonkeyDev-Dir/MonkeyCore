<?php

use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\Project;
use App\Models\StoredFile;
use Database\Seeders\EtaIBackupConfigurationSeeder;
use Database\Seeders\FileTypeSeeder;

beforeEach(function () {
    $this->seed(FileTypeSeeder::class);
});

it('seeds the ETAI project and its backup connection', function () {
    $this->seed(EtaIBackupConfigurationSeeder::class);

    $client = Client::query()->where('tax_id', '3101007178')->firstOrFail();
    $project = Project::query()->where('client_id', $client->id)->where('name', 'Metai - Etai')->firstOrFail();
    $connection = BackupConnection::query()
        ->where('client_id', $client->id)
        ->where('name', 'Metai Backup Config')
        ->firstOrFail();

    expect($client->name)->toBe('Instituto Agropecuario Costarricense S.A.')
        ->and($client->email)->toBe('info@casc.ed.cr')
        ->and($client->phone)->toBe('2475-6622')
        ->and($client->website)->toBe('https://www.casc.ed.cr/casc/')
        ->and($project->code)->toMatch('/^PROJ-[A-Z0-9]{7}$/')
        ->and($project->description)->toBe('Gestor estudiantil')
        ->and($connection->name)->toBe('Metai Backup Config')
        ->and($connection->project_id)->toBe($project->id)
        ->and($connection->ssh_host)->toBe('204.48.27.226')
        ->and($connection->postgres_host)->toBe('localhost')
        ->and($connection->postgres_database)->toBe('etai_iacsa_pg')
        ->and($connection->postgres_user)->toBe('forge');

    $storedFile = StoredFile::query()->where('client_id', $client->id)->firstOrFail();

    expect($client->image_path)->toBe('clients/client-logo-b012479f-08b3-4144-b20a-1cf991447a54.webp')
        ->and($storedFile->identifier)->toBe('b012479f-08b3-4144-b20a-1cf991447a54')
        ->and($storedFile->path)->toBe($client->image_path)
        ->and($storedFile->width)->toBe(217)
        ->and($storedFile->height)->toBe(256);
});

it('seeds the CASC project with its own backup connection', function () {
    $this->seed(EtaIBackupConfigurationSeeder::class);

    $client = Client::query()->where('tax_id', '3101007178')->firstOrFail();
    $project = Project::query()->where('client_id', $client->id)->where('name', 'CASC - Escuela/Colegio')->firstOrFail();
    $connection = BackupConnection::query()->where('client_id', $client->id)->where('name', 'CASC Backup Config')->firstOrFail();

    expect($project->description)->toBe('Gestor estudiantil')
        ->and($project->code)->toMatch('/^PROJ-[A-Z0-9]{7}$/')
        ->and($connection->project_id)->toBe($project->id)
        ->and($connection->postgres_database)->toBe('casc_iacsa_pg')
        ->and($connection->postgres_user)->toBe('forge');
});

it('seeds the student portal with its own backup connection', function () {
    $this->seed(EtaIBackupConfigurationSeeder::class);

    $client = Client::query()->where('tax_id', '3101007178')->firstOrFail();
    $project = Project::query()->where('client_id', $client->id)->where('name', 'Portal estudiantil - Etai')->firstOrFail();
    $connection = BackupConnection::query()->where('client_id', $client->id)->where('name', 'Portal Backup Config')->firstOrFail();

    expect($project->description)->toBe('Plataforma de consulta estudiantil')
        ->and($project->code)->toMatch('/^PROJ-[A-Z0-9]{7}$/')
        ->and($connection->project_id)->toBe($project->id)
        ->and($connection->postgres_database)->toBe('etai_student_profile_iacsa_pg')
        ->and($connection->postgres_user)->toBe('forge');
});

it('keeps connection secrets encrypted in the database', function () {
    $this->seed(EtaIBackupConfigurationSeeder::class);

    $connection = BackupConnection::query()->where('name', 'Metai Backup Config')->firstOrFail();
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
