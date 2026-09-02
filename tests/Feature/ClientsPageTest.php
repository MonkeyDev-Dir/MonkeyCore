<?php

use App\Livewire\Clients\ClientLogoModal;
use App\Livewire\Clients\ClientModal;
use App\Livewire\Clients\ClientsTable;
use App\Livewire\Clients\ProjectCredentialModal;
use App\Livewire\Clients\ProjectModal;
use App\Models\Client;
use App\Models\FileType;
use App\Models\Project;
use App\Models\ProjectCredential;
use App\Models\StoredFile;
use App\Models\User;
use App\Services\ClientService;
use Database\Seeders\FileTypeSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        ->assertSee('<title>Clientes | Monkey</title>', false)
        ->assertSee(__('Clientes'));
});

it('redirects guests from the clients page', function () {
    $this->get(route('clients.index'))->assertRedirectToRoute('login');
});

it('searches clients by name, code, or email and paginates results', function () {
    $user = User::factory()->create();
    Client::factory()->create(['name' => 'Cliente visible', 'code' => 'ABC123', 'email' => 'visible@example.test']);
    Client::factory()->create(['name' => 'Otro cliente', 'code' => 'XYZ789', 'email' => 'otro@example.test']);
    Client::factory()->count(10)->create();

    $table = Livewire::actingAs($user)->test(ClientsTable::class);

    $table->assertSet('search', '')
        ->set('search', 'ABC123')
        ->assertSee('Cliente visible')
        ->assertDontSee('Otro cliente');

    $table->set('search', 'otro@example.test')
        ->assertSee('Otro cliente')
        ->assertDontSee('Cliente visible');
});

it('searches clients without considering case or accents', function () {
    $user = User::factory()->create();
    Client::factory()->create(['name' => 'Árbol Único', 'email' => 'contacto@example.test']);
    Client::factory()->create(['name' => 'Cliente diferente', 'email' => 'otro@example.test']);

    Livewire::actingAs($user)
        ->test(ClientsTable::class)
        ->set('search', 'ARBOL unico')
        ->assertSee('Árbol Único')
        ->assertDontSee('Cliente diferente');
});

it('saves sanitized rich text in a project description', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->call('openCreate', $client->code)
        ->set('name', 'Proyecto enriquecido')
        ->set('description', '<p><strong>Descripción importante</strong></p><script>alert(1)</script>')
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('project-saved');

    $project = Project::query()->where('name', 'Proyecto enriquecido')->firstOrFail();

    expect($project->code)->toMatch('/^PROJ-[A-Z0-9]{7}$/')
        ->and($project->description)->toBe('<p><strong>Descripción importante</strong></p>alert(1)')
        ->and($project->description)->not->toContain('<script>');
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
    Project::factory()->create(['client_id' => $client->id, 'name' => 'Proyecto visible']);

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertSee(route('clients.show', ['clientCode' => $client->code]), false)
        ->assertSee('border-blue-200', false)
        ->assertDontSee('href="'.route('clients.show', ['clientCode' => $client->id]).'"', false);

    $this->get(route('clients.show', ['clientCode' => $client->code]))
        ->assertOk()
        ->assertViewIs('pages.client-profile')
        ->assertSee('Cliente del perfil')
        ->assertSee('lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]', false)
        ->assertSee('space-y-4', false)
        ->assertSee('dark:bg-gray-800', false)
        ->assertSee('currentPath.startsWith(`${normalizedPath}/`)', false)
        ->assertSee(route('clients.backups', ['clientCode' => $client->code]), false)
        ->assertDontSee('client-monthly-backups')
        ->assertSee(__('Correo electrónico'))
        ->assertSee('Proyecto visible')
        ->assertDontSee(__('Código: :code', ['code' => $client->code]));
});

it('edits general client information from the profile modal', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'name' => 'Cliente original',
        'email' => 'original@example.test',
    ]);

    Livewire::actingAs($user)
        ->test(ClientModal::class)
        ->call('openEdit', $client->code)
        ->assertSet('name', 'Cliente original')
        ->set('name', 'Cliente actualizado')
        ->set('email', 'actualizado@example.test')
        ->set('phone', '2475-6622')
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('client-saved');

    expect($client->refresh()->name)->toBe('Cliente actualizado')
        ->and($client->email)->toBe('actualizado@example.test')
        ->and($client->phone)->toBe('2475 6622');
});

it('normalizes client and primary contact phone numbers when saving', function () {
    $client = app(ClientService::class)->save([
        'type' => 'company',
        'name' => 'Cliente con teléfonos normalizados',
        'phone' => '8562-6443',
        'contact' => [
            'name' => 'Contacto principal',
            'phone' => '(123) 45-67',
            'mobile_phone' => '+506 1234 5678',
        ],
    ]);

    expect($client->phone)->toBe('8562 6443')
        ->and($client->contacts()->firstOrFail()->phone)->toBe('123-45-67')
        ->and($client->contacts()->firstOrFail()->mobile_phone)->toBe('50612345678');
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

it('lists client projects and creates one through the profile modal', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->get(route('clients.show', ['clientCode' => $client->code]))
        ->assertSee(__('Proyectos'))
        ->assertSee(__('Nuevo proyecto'))
        ->assertDontSee(__('No hay credenciales registradas.'));

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->call('openCreate', $client->code)
        ->set('name', 'Portal del cliente')
        ->set('description', 'Proyecto de integración')
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('project-saved');

    $project = Project::query()->where('name', 'Portal del cliente')->firstOrFail();

    expect($project->client_id)->toBe($client->id)
        ->and($project->code)->toStartWith('PROJ-')
        ->and($project->description)->toBe('Proyecto de integración');

});

it('edits a project from its client profile', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id, 'name' => 'Proyecto original']);

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->call('openEdit', $client->code, $project->id)
        ->assertSet('name', 'Proyecto original')
        ->set('name', 'Proyecto actualizado')
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('project-saved');

    expect($project->refresh()->name)->toBe('Proyecto actualizado');
});

it('creates and updates shared project credentials with an encrypted password', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);

    $credentialModal = Livewire::actingAs($user)
        ->test(ProjectCredentialModal::class)
        ->call('openCreate', $client->code, $project->id)
        ->set('name', 'WordPress producción')
        ->set('type', 'wordpress')
        ->set('loginUrl', 'https://example.test/wp-admin')
        ->set('username', 'admin@example.test')
        ->set('password', 'secret-password')
        ->set('notes', 'Acceso principal')
        ->assertSet('password', 'secret-password');

    $credentialModal
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('project-credential-saved');

    $credential = ProjectCredential::query()->where('project_id', $project->id)->firstOrFail();

    expect($credential->password)->toBe('secret-password')
        ->and($credential->getRawOriginal('password'))->not->toBe('secret-password');

    Livewire::actingAs($user)
        ->test(ProjectCredentialModal::class)
        ->call('openEdit', $client->code, $project->id, $credential->id)
        ->set('username', 'editor@example.test')
        ->call('save');

    expect($credential->refresh()->username)->toBe('editor@example.test')
        ->and($credential->password)->toBe('secret-password');
});

it('does not allow editing a project from another client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $otherClient->id]);

    expect(fn () => Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->call('openEdit', $client->code, $project->id))
        ->toThrow(ModelNotFoundException::class);
});

it('validates a project name as required', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();

    Livewire::actingAs($user)
        ->test(ProjectModal::class)
        ->call('openCreate', $client->code)
        ->call('save')
        ->assertHasErrors(['name']);
});

it('limits project card details to 120 characters before expanding', function () {
    $project = Project::factory()->make([
        'description' => str_repeat('Detalle del proyecto. ', 10),
    ]);

    expect($project->hasLongDescription())->toBeTrue()
        ->and($project->descriptionPreview())->toEndWith('...')
        ->and(mb_strlen($project->descriptionPreview()))->toBe(123);
});
