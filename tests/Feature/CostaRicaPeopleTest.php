<?php

use App\Models\CivilRegistryRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function createPeopleApiToken(User $user): string
{
    return $user->createToken('ecommerce-people-test', ['people:read'])->plainTextToken;
}

it('consults a person in Costa Rica through ApifyCR', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');

    Http::preventStrayRequests();
    Http::fake([
        'https://tse.apifycr.com/api/v2/cedula?cedula=123456789' => Http::response([
            'status' => 'success',
            'data' => [
                'cedula' => '123456789',
                'nombre' => 'JUAN',
                'apellido1' => 'PEREZ',
                'apellido2' => 'MORA',
                'codelec' => '001',
                'fecha_caduc' => '2030-01-01',
                'provincia' => 'SAN JOSE',
                'canton' => 'CENTRAL',
                'distrito' => 'CARMEN',
                'padre' => 'JUAN PADRE',
                'cedula_padre' => '101010101',
                'madre' => 'JUANA MADRE',
                'cedula_madre' => '202020202',
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $this->withToken(createPeopleApiToken($user))
        ->postJson(route('api.v1.people.show'), ['cedula' => '123456789'])
        ->assertOk()
        ->assertJsonPath('data.cedula', '123456789')
        ->assertJsonPath('data.nombre', 'Juan')
        ->assertJsonPath('data.apellido1', 'Perez')
        ->assertJsonPath('data.apellido2', 'Mora')
        ->assertJsonPath('data.codelec', '001')
        ->assertJsonPath('data.fecha_caduc', '2030-01-01')
        ->assertJsonPath('data.provincia', 'SAN JOSE')
        ->assertJsonPath('data.canton', 'CENTRAL')
        ->assertJsonPath('data.distrito', 'CARMEN')
        ->assertJsonMissingPath('data.padre')
        ->assertJsonMissingPath('data.cedula_padre')
        ->assertJsonMissingPath('data.madre')
        ->assertJsonMissingPath('data.cedula_madre');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://tse.apifycr.com/api/v2/cedula?cedula=123456789'
            && $request->hasHeader('Authorization', 'Bearer test-api-key');
    });

    $logPath = glob(storage_path('logs/integrations/civil-registry/civil-registry-*.log'))[0] ?? null;

    expect($logPath)->not->toBeNull();
    expect(file_get_contents($logPath))
        ->toContain('Consulta de persona completada')
        ->toContain('1******89');

    expect(config('request-logs.civil_registry.max_files'))->toBe(30);
});

it('accepts a direct person response from ApifyCR', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');

    Http::preventStrayRequests();
    Http::fake([
        'https://tse.apifycr.com/api/v2/cedula?cedula=123456789' => Http::response([
            'cedula' => '123456789',
            'nombre' => 'JUAN',
            'apellido1' => 'PEREZ',
            'apellido2' => 'MORA',
        ]),
    ]);

    $user = User::factory()->create();

    $this->withToken(createPeopleApiToken($user))
        ->postJson(route('api.v1.people.show'), ['cedula' => '123456789'])
        ->assertOk()
        ->assertJsonPath('data.cedula', '123456789')
        ->assertJsonPath('data.apellido1', 'Perez');
});

it('reuses a recent person consultation without calling ApifyCR again', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');

    Http::preventStrayRequests();
    Http::fake([
        'https://tse.apifycr.com/api/v2/cedula?cedula=123456789' => Http::response([
            'cedula' => '123456789',
            'nombre' => 'JUAN',
            'apellido1' => 'PEREZ',
            'apellido2' => 'MORA',
            'codelec' => '001',
            'fecha_caduc' => '2030-01-01',
            'provincia' => 'SAN JOSE',
            'canton' => 'CENTRAL',
            'distrito' => 'CARMEN',
            'padre' => 'JUAN PADRE',
            'cedula_padre' => '101010101',
            'madre' => 'JUANA MADRE',
            'cedula_madre' => '202020202',
        ]),
    ]);

    $user = User::factory()->create();
    $token = createPeopleApiToken($user);

    $this->withToken($token)->postJson(route('api.v1.people.show'), ['cedula' => '123456789'])->assertOk();
    $this->withToken($token)->postJson(route('api.v1.people.show'), ['cedula' => '123456789'])->assertOk();

    Http::assertSentCount(1);
    $this->assertDatabaseHas('civil_registry_records', [
        'type' => CivilRegistryRecord::TypePerson,
        'identification' => '123456789',
        'name' => 'Juan',
        'first_surname' => 'Perez',
        'second_surname' => 'Mora',
        'electoral_code' => '001',
        'province' => 'SAN JOSE',
        'canton' => 'CENTRAL',
        'district' => 'CARMEN',
        'father_name' => 'JUAN PADRE',
        'father_identification' => '101010101',
        'mother_name' => 'JUANA MADRE',
        'mother_identification' => '202020202',
        'found' => true,
    ]);
});

it('refreshes an expired person consultation', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');

    CivilRegistryRecord::query()->create([
        'type' => CivilRegistryRecord::TypePerson,
        'identification' => '123456789',
        'name' => 'NOMBRE ANTERIOR',
        'found' => true,
        'consulted_at' => CarbonImmutable::now()->subYears(2)->subSecond(),
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://tse.apifycr.com/api/v2/cedula?cedula=123456789' => Http::response([
            'cedula' => '123456789',
            'nombre' => 'NOMBRE NUEVO',
        ]),
    ]);

    $user = User::factory()->create();

    $this->withToken(createPeopleApiToken($user))
        ->postJson(route('api.v1.people.show'), ['cedula' => '123456789'])
        ->assertOk()
        ->assertJsonPath('data.nombre', 'Nombre Nuevo');

    Http::assertSentCount(1);
    $this->assertDatabaseHas('civil_registry_records', [
        'identification' => '123456789',
        'name' => 'Nombre Nuevo',
    ]);
});

it('does not allow guests to consult Costa Rica people data', function () {
    $this->postJson(route('api.v1.people.show'), ['cedula' => '123456789'])
        ->assertUnauthorized();
});

it('rejects malformed Costa Rica identity numbers', function () {
    $user = User::factory()->create();

    $this->withToken(createPeopleApiToken($user))
        ->postJson('/api/v1/people', ['cedula' => '123'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cedula');
});

it('exposes the civil registry people endpoint from the Core', function () {
    $record = CivilRegistryRecord::query()->create([
        'type' => CivilRegistryRecord::TypePerson,
        'identification' => '123456789',
        'name' => 'PERSONA LOCAL',
        'found' => true,
        'consulted_at' => now(),
    ]);

    Http::preventStrayRequests();
    $user = User::factory()->create();

    $this->withToken(createPeopleApiToken($user))
        ->postJson(route('api.v1.civil-registry.people.show'), ['cedula' => $record->identification])
        ->assertOk()
        ->assertJsonPath('data.nombre', 'Persona Local');
});

it('exposes the civil registry legal entities endpoint from the Core', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');

    Http::preventStrayRequests();
    Http::fake([
        'https://tse.apifycr.com/api/v2/juridica?cedula=1234567890' => Http::response([
            'nombre' => 'EMPRESA CENTRAL',
            'tipoIdentificacion' => '02',
        ]),
    ]);

    $user = User::factory()->create();

    $this->withToken(createPeopleApiToken($user))
        ->postJson(route('api.v1.civil-registry.legal-entities.show'), ['cedula' => '1234567890'])
        ->assertOk()
        ->assertJsonPath('data.nombre', 'Empresa Central')
        ->assertJsonPath('data.tipoIdentificacion', '02');
});
