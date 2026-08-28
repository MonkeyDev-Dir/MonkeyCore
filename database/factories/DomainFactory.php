<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Domain>
 */
class DomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->unique()->domainName(),
            'hosting_provider' => fake()->company(),
            'annual_cost' => fake()->randomFloat(2, 100, 2500),
            'currency' => 'CRC',
            'expires_at' => Carbon::now()->addYear()->toDateString(),
            'renewal_period_years' => 1,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
