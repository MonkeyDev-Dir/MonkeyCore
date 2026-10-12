<?php

use App\Models\CivilRegistryRecord;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('shows the BCCR integration status and last execution', function () {
    config()->set('services.bccr.token', 'test-token');
    ExchangeRate::factory()->create(['updated_at' => Carbon::parse('2026-08-29 06:30:00', 'UTC')]);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('integrations.index'));

    $response->assertOk()
        ->assertSeeText('Tipo de cambio BCCR')
        ->assertSeeText('Activa')
        ->assertSeeText('29/08/2026 00:30');
});

it('shows the ApifyCR integration status and last consultation', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');
    CivilRegistryRecord::query()->create(['type' => CivilRegistryRecord::TypePerson, 'identification' => '123456789', 'found' => true, 'consulted_at' => Carbon::parse('2026-08-30 12:45:00', 'UTC')]);

    $this->actingAs(User::factory()->create())
        ->get(route('integrations.index'))
        ->assertOk()
        ->assertSeeText('ApifyCR')
        ->assertSeeText('Activa')
        ->assertSeeText('30/08/2026 06:45')
        ->assertSee('aria-label="Probar servicio"', false);
});

it('shows the BCCR integration as inactive without a token', function () {
    config()->set('services.bccr.token', null);

    $this->actingAs(User::factory()->create())
        ->get(route('integrations.index'))
        ->assertOk()
        ->assertSeeText('Inactiva')
        ->assertSeeText('Sin ejecuciones registradas');
});

it('does not allow guests to view integrations', function () {
    $this->get(route('integrations.index'))
        ->assertRedirect(route('login'));
});

it('returns the complete response from the ApifyCR diagnostic query', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');
    Http::preventStrayRequests();
    Http::fake([
        'connect.apifycr.com/api/v2/cedula*' => Http::response([
            'status' => 'success',
            'data' => [
                'cedula' => '123456789',
                'nombre' => 'JUAN PEREZ',
            ],
        ]),
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->post(route('integrations.apifycr.test'));

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('status', 200)
        ->assertJsonPath('response.data.nombre', 'JUAN PEREZ');

    Http::assertSent(function (Request $request): bool {
        return preg_match('#/api/v2/cedula\?cedula=113420(?:2\d{2}|[3-9]\d{2})$#', $request->url()) === 1;
    });
});

it('reports the ApifyCR retry window when the provider is rate limited', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');
    Http::preventStrayRequests();
    Http::fake([
        'connect.apifycr.com/api/v2/cedula*' => Http::response([
            'error' => 'Demasiadas solicitudes.',
        ], 429, ['Retry-After' => '45']),
    ]);

    $user = User::factory()->create();
    RateLimiter::clear(md5('apifycr-test'.$user->email));

    $this->actingAs($user)
        ->post(route('integrations.apifycr.test'))
        ->assertOk()
        ->assertJsonPath('ok', false)
        ->assertJsonPath('status', 429)
        ->assertJsonPath('retry_after', 45)
        ->assertJsonPath('error', 'Demasiadas solicitudes.');
});

it('limits repeated ApifyCR diagnostic requests per user', function () {
    config()->set('services.apifycr.api_key', 'test-api-key');
    Http::preventStrayRequests();
    Http::fake([
        'connect.apifycr.com/api/v2/cedula*' => Http::response([
            'status' => 'success',
        ]),
    ]);

    $user = User::factory()->create();
    RateLimiter::clear(md5('apifycr-test'.$user->email));

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('integrations.apifycr.test'))
        ->assertOk();

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('integrations.apifycr.test'))
        ->assertTooManyRequests();

    Http::assertSentCount(1);
});

it('allows an authenticated user to run the BCCR integration manually', function () {
    config()->set('services.bccr.token', 'test-token');
    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        preg_match('#/indicadoresEconomicos/(\d+)/series#', $request->url(), $matches);
        $indicator = $matches[1] ?? null;

        return Http::response([
            'datos' => [[
                'codigoIndicador' => $indicator,
                'series' => [[
                    'fecha' => '2026-08-28',
                    'valorDatoPorPeriodo' => ['317' => '500.25', '318' => '507.80', '333' => '1.1767'][$indicator] ?? 0,
                ]],
            ]],
        ]);
    });

    $this->actingAs(User::factory()->create())
        ->post(route('integrations.sync'))
        ->assertRedirect(route('integrations.index'));

    $this->assertDatabaseCount('exchange_rates', 4);
});
