<?php

namespace Database\Factories;

use App\Models\WorkItemType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WorkItemType> */
class WorkItemTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $name = is_array($name) ? implode(' ', $name) : $name;

        return ['name' => $name, 'slug' => Str::slug($name), 'is_active' => true];
    }
}
