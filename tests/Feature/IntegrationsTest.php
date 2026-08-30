<?php

use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

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
