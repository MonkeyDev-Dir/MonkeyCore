<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeBccrExchangeRates(): void
{
    config()->set('services.bccr.token', 'test-token');
    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        preg_match('#/indicadoresEconomicos/(\d+)/series#', $request->url(), $matches);
        $indicator = $matches[1] ?? null;
        $values = ['317' => '500.25', '318' => '507.80', '333' => '1.1767'];

        return Http::response([
            'datos' => [[
                'codigoIndicador' => $indicator,
                'series' => [[
                    'fecha' => '2026-08-28',
                    'valorDatoPorPeriodo' => $values[$indicator] ?? 0,
                ]],
            ]],
        ]);
    });
}

it('stores the BCCR exchange rates for a date', function () {
    fakeBccrExchangeRates();

    $this->artisan('exchange-rates:sync', ['--date' => '2026-08-28'])
        ->expectsOutput('Tipos de cambio almacenados: 4')
        ->assertSuccessful();

    $this->assertDatabaseCount('exchange_rates', 4);
    $this->assertDatabaseHas('exchange_rates', [
        'rate_date' => '2026-08-28',
        'currency' => 'USD',
        'rate_type' => 'buy',
        'indicator_code' => 317,
        'value' => 500.25,
    ]);
    $this->assertDatabaseHas('exchange_rates', [
        'currency' => 'EUR',
        'rate_type' => 'buy',
        'indicator_code' => 333,
        'value' => 588.644175,
    ]);
    $this->assertDatabaseHas('exchange_rates', [
        'currency' => 'EUR',
        'rate_type' => 'sell',
        'indicator_code' => 333,
        'value' => 597.52826,
    ]);
});

it('does not duplicate rates when the synchronization is repeated', function () {
    fakeBccrExchangeRates();

    $this->artisan('exchange-rates:sync', ['--date' => '2026-08-28'])->assertSuccessful();
    $this->artisan('exchange-rates:sync', ['--date' => '2026-08-28'])->assertSuccessful();

    $this->assertDatabaseCount('exchange_rates', 4);
});

it('returns failure and stores nothing when the BCCR request fails', function () {
    config()->set('services.bccr.token', 'test-token');
    Http::preventStrayRequests();
    Http::fake([
        config('services.bccr.base_url').'/indicadoresEconomicos/*/series*' => Http::response('service unavailable', 503),
    ]);

    $this->artisan('exchange-rates:sync', ['--date' => '2026-08-28'])
        ->expectsOutputToContain('HTTP request returned status code 503')
        ->assertFailed();

    $this->assertDatabaseCount('exchange_rates', 0);
});

it('rejects an invalid synchronization date', function () {
    $this->artisan('exchange-rates:sync', ['--date' => '28/08/2026'])
        ->expectsOutput('La fecha debe tener el formato YYYY-MM-DD.')
        ->assertFailed();
});
