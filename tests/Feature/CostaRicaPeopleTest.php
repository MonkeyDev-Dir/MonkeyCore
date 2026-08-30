<?php

use App\Models\User;
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
                'primer_apellido' => 'PEREZ',
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $this->withToken(createPeopleApiToken($user))
        ->getJson(route('api.v1.people.show', '123456789'))
        ->assertOk()
        ->assertJsonPath('data.cedula', '123456789')
        ->assertJsonPath('data.nombre', 'JUAN');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://tse.apifycr.com/api/v2/cedula?cedula=123456789'
            && $request->hasHeader('Authorization', 'Bearer test-api-key');
    });

    $logPath = glob(storage_path('logs/request/apifycr-*.log'))[0] ?? null;

    expect($logPath)->not->toBeNull();
    expect(file_get_contents($logPath))
        ->toContain('Consulta de persona completada')
        ->toContain('1******89');

    expect(config('request-logs.apifycr.max_files'))->toBe(30);
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
        ->getJson(route('api.v1.people.show', '123456789'))
        ->assertOk()
        ->assertJsonPath('data.cedula', '123456789')
        ->assertJsonPath('data.apellido1', 'PEREZ');
});

it('does not allow guests to consult Costa Rica people data', function () {
    $this->getJson(route('api.v1.people.show', '123456789'))
        ->assertUnauthorized();
});

it('rejects malformed Costa Rica identity numbers', function () {
    $user = User::factory()->create();

    $this->withToken(createPeopleApiToken($user))
        ->getJson('/api/v1/people/123')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cedula');
});
