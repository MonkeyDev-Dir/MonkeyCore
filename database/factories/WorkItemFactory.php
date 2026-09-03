<?php

namespace Database\Factories;

use App\Enums\WorkItemPriority;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkItem> */
class WorkItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'project_id' => Project::factory(),
            'work_item_type_id' => WorkItemType::factory(),
            'created_by' => User::factory(),
            'priority' => WorkItemPriority::Medium,
            'title' => fake()->sentence(6),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
