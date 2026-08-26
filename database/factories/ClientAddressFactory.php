<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientAddress>
 */
class ClientAddressFactory extends Factory
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
            'type' => 'main',
            'address_line' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['San José', 'Heredia', 'Alajuela']),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'is_primary' => true,
        ];
    }
}
