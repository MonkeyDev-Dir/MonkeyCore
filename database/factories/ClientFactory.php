<?php

namespace Database\Factories;

use App\Helpers\RandomHelper;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => RandomHelper::generateUniqueDigits(6, 'clients'),
            'type' => 'company',
            'name' => fake()->company(),
            'legal_name' => fake()->company(),
            'tax_id' => fake()->numerify('##########'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'details' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }
}
