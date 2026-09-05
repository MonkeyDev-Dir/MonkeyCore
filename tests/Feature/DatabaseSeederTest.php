<?php

use App\Models\ApiConsumer;
use App\Models\BackupConnection;
use App\Models\Client;
use App\Models\Domain;
use App\Models\FileType;
use App\Models\Project;
use App\Models\StoredFile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('seeds the initial user and configured clients', function () {
    Storage::fake('public');

    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(1)
        ->and(Client::count())->toBe(3)
        ->and(Client::query()->pluck('code')->unique())->toHaveCount(3)
        ->and(Client::query()->where('tax_id', '3101796338')->value('name'))->toBe('Granitos y Mármoles CR')
        ->and(Client::query()->where('tax_id', '3101796338')->value('phone'))->toBe('8562 6443')
        ->and(Client::query()->where('tax_id', '3101796338')->value('website'))->toBe('https://gymcr.co.cr/')
        ->and(Client::query()->where('tax_id', '113420689')->value('email'))->toBe('info@monkeysolutions.co')
        ->and(Client::query()->where('tax_id', '113420689')->value('website'))->toBe('https://monkeysolutions.co')
        ->and(Domain::query()->where('name', 'pruebayerror.com')->value('hosting_provider'))->toBe('DonDominio')
        ->and((string) Domain::query()->where('name', 'pruebayerror.com')->value('annual_cost'))->toBe('18.53')
        ->and(Domain::query()->where('name', 'pruebayerror.com')->value('expires_at')->format('Y-m-d'))->toBe('2027-01-14')
        ->and(Domain::query()->where('name', 'monkeysolutions.co')->value('hosting_provider'))->toBe('Namecheap')
        ->and((string) Domain::query()->where('name', 'monkeysolutions.co')->value('annual_cost'))->toBe('45.48')
        ->and(Domain::query()->where('name', 'monkeysolutions.co')->value('expires_at')->format('Y-m-d'))->toBe('2027-08-20')
        ->and(Project::query()->where('name', 'Core')->count())->toBe(1)
        ->and(Project::query()->where('name', 'Sitio web')->where('client_id', Client::query()->where('tax_id', '113420689')->value('id'))->count())->toBe(1)
        ->and(Client::query()->where('tax_id', '3101796338')->value('image_path'))->toBe('clients/client-logo-8ac01a80-99d8-469f-8ef2-74a60cf6f47a.webp')
        ->and(Domain::query()->where('name', 'gymcr.co.cr')->count())->toBe(1)
        ->and(BackupConnection::where('name', 'Metai Backup Config')->count())->toBe(1)
        ->and(BackupConnection::where('name', 'CASC Backup Config')->count())->toBe(1)
        ->and(BackupConnection::where('name', 'Portal Backup Config')->count())->toBe(1)
        ->and(User::where('email', 'me@gilberthrojas.com')->exists())->toBeTrue()
        ->and(User::whereNotNull('avatar_path')->count())->toBe(1)
        ->and(ApiConsumer::query()->where('name', 'Postman')->count())->toBe(1)
        ->and(ApiConsumer::query()->where('name', 'Postman')->value('description'))->toBe('Llave para pruebas con Postman')
        ->and(ApiConsumer::query()->where('name', 'Postman')->value('active'))->toBeTrue()
        ->and(ApiConsumer::query()->where('name', 'Postman')->firstOrFail()->tokens()->count())->toBe(0);

    $domain = Domain::query()->where('name', 'gymcr.co.cr')->firstOrFail();

    expect($domain->hosting_provider)->toBe('Dominios CR')
        ->and((string) $domain->annual_cost)->toBe('28.25')
        ->and($domain->currency)->toBe('USD')
        ->and($domain->expires_at->format('Y-m-d'))->toBe('2027-07-12')
        ->and($domain->renewal_period_years)->toBe(1);

    $project = Project::query()->where('name', 'Sitio web')->firstOrFail();

    expect($project->client_id)->toBe(Client::query()->where('tax_id', '3101796338')->value('id'))
        ->and($project->code)->toMatch('/^PROJ-[A-Z0-9]{7}$/')
        ->and($project->description)->toBe('Sitio web desarrollado con Wordpress.');

    $storedFile = StoredFile::query()
        ->where('client_id', Client::query()->where('tax_id', '3101796338')->value('id'))
        ->where('file_type_id', FileType::query()->where('key', FileType::ClientLogo)->value('id'))
        ->firstOrFail();

    expect($storedFile->identifier)->toBe('8ac01a80-99d8-469f-8ef2-74a60cf6f47a')
        ->and($storedFile->path)->toBe('clients/client-logo-8ac01a80-99d8-469f-8ef2-74a60cf6f47a.webp')
        ->and($storedFile->url)->toBe('https://monkey-core-bucket.s3.us-east-2.amazonaws.com/clients/client-logo-8ac01a80-99d8-469f-8ef2-74a60cf6f47a.webp')
        ->and($storedFile->width)->toBe(256)
        ->and($storedFile->height)->toBe(88);

    User::query()->pluck('avatar_path')->each(function (?string $avatarPath): void {
        expect($avatarPath)->toStartWith('avatars/');
        Storage::disk('public')->assertExists($avatarPath);
    });
});
