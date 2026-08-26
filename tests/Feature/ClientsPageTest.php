<?php

use App\Livewire\Clients\ClientLogoModal;
use App\Livewire\Clients\ClientModal;
use App\Models\Client;
use App\Models\FileType;
use App\Models\StoredFile;
use App\Models\User;
use App\Services\ClientService;
use Database\Seeders\FileTypeSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('shows the clients page to authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertViewIs('pages.clients')
        ->assertSee(__('Clientes'));
});

it('redirects guests from the clients page', function () {
    $this->get(route('clients.index'))->assertRedirectToRoute('login');
});

it('creates a company with its primary contact, address, and image', function () {
    Storage::fake('s3');
    $this->seed(FileTypeSeeder::class);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ClientModal::class)
        ->call('openCreate')
        ->set('type', 'company')
        ->set('name', 'Acme Solutions')
        ->set('legalName', 'Acme Solutions S.A.')
        ->set('email', 'hello@acme.test')
        ->set('image', UploadedFile::fake()->image('logo.png'))
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('client-saved');

    $client = Client::query()->where('name', 'Acme Solutions')->firstOrFail();

    expect($client->contacts()->where('is_primary', true)->exists())->toBeFalse()
        ->and($client->addresses()->where('is_primary', true)->exists())->toBeFalse()
        ->and($client->image_path)->toStartWith('clients/');

    $storedFile = $client->storedFiles()->firstOrFail();

    expect($storedFile->client_id)->toBe($client->id)
        ->and($storedFile->user_id)->toBeNull()
        ->and($storedFile->fileType->key)->toBe(FileType::ClientLogo)
        ->and($storedFile->fileType->name)->toBe('Logo de cliente')
        ->and($storedFile->identifier)->not->toBeEmpty()
        ->and($storedFile->name)->toStartWith('client-logo-')
        ->and($storedFile->url)->toBe(Storage::disk('s3')->url($storedFile->path))
        ->and((float) $storedFile->size_mb)->toBeGreaterThan(0)
        ->and($storedFile->format)->toBe('webp')
        ->and($storedFile->width)->toBe(10)
        ->and($storedFile->height)->toBe(10)
        ->and($storedFile->bucket)->toBe((string) config('filesystems.disks.s3.bucket'))
        ->and($storedFile->mime_type)->toBe('image/webp');

    Storage::disk('s3')->assertExists($storedFile->path);
});

it('assigns a unique short code to every client', function () {
    $firstClient = Client::factory()->create();
    $secondClient = Client::factory()->create();

    expect($firstClient->code)->toHaveLength(6)
        ->and($secondClient->code)->toHaveLength(6)
        ->and($firstClient->code)->not->toBe($secondClient->code);
});

it('stores the dimensions of the resized client logo', function () {
    Storage::fake('s3');
    $this->seed(FileTypeSeeder::class);
    $user = User::factory()->create();

    app(ClientService::class)->save([
        'type' => 'company',
        'name' => 'Cliente con logo redimensionado',
        'created_by' => $user->id,
    ], null, UploadedFile::fake()->image('logo.png', 1000, 930));

    $storedFile = StoredFile::query()->firstOrFail();

    expect($storedFile->width)->toBe(256)
        ->and($storedFile->height)->toBe(238)
        ->and((float) $storedFile->size_mb)->toBeGreaterThan(0);
});

it('does not create client records when the image cannot be uploaded', function () {
    $this->seed(FileTypeSeeder::class);
    $user = User::factory()->create();

    $disk = Mockery::mock();
    $disk->shouldReceive('url')
        ->once()
        ->andReturn('https://s3.test/client-logo.webp');
    $disk->shouldReceive('put')
        ->once()
        ->andThrow(new RuntimeException('S3 unavailable'));
    $disk->shouldNotReceive('delete');
    Storage::shouldReceive('disk')
        ->once()
        ->with('s3')
        ->andReturn($disk);

    expect(fn () => app(ClientService::class)->save([
        'type' => 'company',
        'name' => 'Cliente sin imagen',
        'created_by' => $user->id,
    ], null, UploadedFile::fake()->image('logo.png')))
        ->toThrow(RuntimeException::class);

    expect(Client::query()->count())->toBe(0)
        ->and(StoredFile::query()->count())->toBe(0);
});

it('removes the uploaded image when the database transaction fails', function () {
    Storage::fake('s3');
    $this->seed(FileTypeSeeder::class);

    expect(fn () => app(ClientService::class)->save([
        'type' => 'company',
        'name' => 'Cliente sin registro',
        'created_by' => 999999,
    ], null, UploadedFile::fake()->image('logo.png')))
        ->toThrow(QueryException::class);

    expect(Client::query()->count())->toBe(0)
        ->and(StoredFile::query()->count())->toBe(0)
        ->and(Storage::disk('s3')->allFiles())->toBeEmpty();
});

it('shows only the initial information when creating a client', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ClientModal::class)
        ->call('openCreate')
        ->assertSee(__('Cargando información...'))
        ->assertSee(__('Tipo'))
        ->assertDontSee(__('Razón social'))
        ->set('type', 'person')
        ->assertDontSee(__('Identificación fiscal'))
        ->assertDontSee(__('Detalles'))
        ->assertDontSee(__('Contacto principal'))
        ->assertDontSee(__('Dirección principal'));
});

it('fills a person client from the Costa Rica identity registry', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');
    config()->set('services.gemini.enabled', false);

    Http::preventStrayRequests();
    Http::fake([
        'https://tse.apifycr.com/api/v2/cedula?cedula=123456789' => Http::response([
            'status' => 'success',
            'data' => [
                'cedula' => '123456789',
                'nombre' => 'JUAN',
                'primer_apellido' => 'PEREZ',
                'segundo_apellido' => 'MORA',
            ],
        ]),
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ClientModal::class)
        ->call('openCreate')
        ->set('type', 'person')
        ->set('taxId', '123456789')
        ->call('lookupPerson')
        ->assertSet('name', 'Juan Perez Mora')
        ->assertSet('taxId', '123456789');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://tse.apifycr.com/api/v2/cedula?cedula=123456789');
});

it('fills a company client from the Costa Rica legal entity registry', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');
    config()->set('services.gemini.enabled', false);

    Http::preventStrayRequests();
    Http::fake([
        'https://tse.apifycr.com/api/v2/juridica?cedula=1234567890' => Http::response([
            'nombre' => 'APIFY LATAM SOCIEDAD ANONIMA',
            'tipoIdentificacion' => '02',
        ]),
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ClientModal::class)
        ->call('openCreate')
        ->set('taxId', '1234567890')
        ->call('lookupCompany')
        ->assertSet('name', 'Apify Latam Sociedad Anonima')
        ->assertSet('taxId', '1234567890');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://tse.apifycr.com/api/v2/juridica?cedula=1234567890');
});

it('opens the client profile from the clients table', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['name' => 'Cliente del perfil']);

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertSee(route('clients.show', ['clientCode' => $client->code]), false)
        ->assertSee('border-blue-300', false)
        ->assertDontSee('href="'.route('clients.show', ['clientCode' => $client->id]).'"', false);

    $this->get(route('clients.show', ['clientCode' => $client->code]))
        ->assertOk()
        ->assertViewIs('pages.client-profile')
        ->assertSee('Cliente del perfil')
        ->assertDontSee(__('Código: :code', ['code' => $client->code]));
});

it('updates a client logo from the profile modal and dispatches an update event', function () {
    Storage::fake('s3');
    $this->seed(FileTypeSeeder::class);
    $user = User::factory()->create();
    $client = Client::factory()->create();

    Livewire::actingAs($user)
        ->test(ClientLogoModal::class)
        ->call('open', $client->code)
        ->assertSee(__('Logo actual'))
        ->assertSee(__('Vista previa'))
        ->set('image', UploadedFile::fake()->image('new-logo.png'))
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('client-logo-updated');

    expect($client->storedFiles()->count())->toBe(1);
    Storage::disk('s3')->assertCount('clients', 1);
});

it('validates the client name and image type', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ClientModal::class)
        ->call('openCreate')
        ->set('image', UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['name', 'image']);
});
