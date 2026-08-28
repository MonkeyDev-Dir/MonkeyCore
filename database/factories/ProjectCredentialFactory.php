<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectCredential>
 */
class ProjectCredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(2, true),
            'type' => 'wordpress',
            'login_url' => fake()->url(),
            'username' => fake()->userName(),
            'password' => fake()->password(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
