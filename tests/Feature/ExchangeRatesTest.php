<?php

use App\Models\ExchangeRate;
use App\Models\User;

function createExchangeRateApiToken(User $user): string
{
    return $user->createToken('ecommerce-test')->plainTextToken;
}

function seedExchangeRates(string $date = '2026-08-28'): void
{
    ExchangeRate::factory()->createMany([
        ['rate_date' => $date, 'currency' => 'USD', 'rate_type' => 'buy', 'indicator_code' => 317, 'value' => 500.25],
        ['rate_date' => $date, 'currency' => 'USD', 'rate_type' => 'sell', 'indicator_code' => 318, 'value' => 507.80],
        ['rate_date' => $date, 'currency' => 'EUR', 'rate_type' => 'buy', 'indicator_code' => 333, 'value' => 588.644175],
        ['rate_date' => $date, 'currency' => 'EUR', 'rate_type' => 'sell', 'indicator_code' => 333, 'value' => 597.52826],
    ]);
}

it('returns the latest centrally stored exchange rates to an authorized consumer', function () {
    seedExchangeRates();
    $user = User::factory()->create();

    $this->withToken(createExchangeRateApiToken($user))
        ->getJson('/api/v1/exchange-rates/latest')
        ->assertOk()
        ->assertJsonPath('data.date', '2026-08-28')
        ->assertJsonPath('data.source', 'BCCR')
        ->assertJsonPath('data.rates.0.currency', 'EUR')
        ->assertJsonPath('data.rates.2.value', '500.25000000');
});

it('returns exchange rates for a requested date without contacting the BCCR', function () {
    seedExchangeRates();
    $user = User::factory()->create();

    $this->withToken(createExchangeRateApiToken($user))
        ->getJson('/api/v1/exchange-rates/2026-08-28')
        ->assertOk()
        ->assertJsonPath('data.date', '2026-08-28')
        ->assertJsonCount(4, 'data.rates');
});

it('allows any authenticated token to access the exchange rates API', function () {
    $user = User::factory()->create();
    $token = $user->createToken('ecommerce-test', ['unrelated:ability'])->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/exchange-rates/latest')
        ->assertNotFound();
});

it('rejects guests from the consumer API', function () {
    $this->getJson('/api/v1/exchange-rates/latest')
        ->assertUnauthorized();
});

it('returns not found when no exchange rates are available', function () {
    $user = User::factory()->create();

    $this->withToken(createExchangeRateApiToken($user))
        ->getJson('/api/v1/exchange-rates/latest')
        ->assertNotFound();
});

it('exposes a public health endpoint', function () {
    $this->getJson('/api/v1/public/health')
        ->assertOk()
        ->assertJsonPath('data.status', 'ok');
});
