<?php

namespace Database\Factories;

use App\Models\WorkItemCategory;
use App\Models\WorkItemType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WorkItemCategory> */
class WorkItemCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $name = is_array($name) ? implode(' ', $name) : $name;

        return ['work_item_type_id' => WorkItemType::factory(), 'name' => $name, 'slug' => Str::slug($name), 'is_active' => true];
    }
}
