<?php

namespace Database\Factories;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rate_date' => now()->toDateString(),
            'currency' => ExchangeRate::CurrencyUsd,
            'rate_type' => ExchangeRate::TypeBuy,
            'indicator_code' => 317,
            'value' => 500.00000000,
        ];
    }
}
