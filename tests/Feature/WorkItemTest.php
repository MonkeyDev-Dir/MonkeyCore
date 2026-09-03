<?php

use App\Enums\WorkItemEntryVisibility;
use App\Enums\WorkItemOrigin;
use App\Enums\WorkItemPriority;
use App\Enums\WorkItemStatus;
use App\Models\WorkItemType;
use Database\Seeders\WorkItemTypeSeeder;

it('defines the stable work item domain values', function () {
    expect(WorkItemStatus::cases())->toHaveCount(10)
        ->and(WorkItemPriority::cases())->toHaveCount(4)
        ->and(WorkItemOrigin::cases())->toHaveCount(4)
        ->and(WorkItemEntryVisibility::cases())->toHaveCount(2);
});

it('seeds the initial work item types and categories', function () {
    $this->seed(WorkItemTypeSeeder::class);

    expect(WorkItemType::query()->where('slug', 'support')->exists())->toBeTrue()
        ->and(WorkItemType::query()->where('slug', 'development')->firstOrFail()->categories)->toHaveCount(3);
});
