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

it('provides the configured colors for work item priorities and statuses', function () {
    expect(WorkItemPriority::High->label())->toBe(__('Alta'))
        ->and(WorkItemPriority::High->color())->toBe('bg-orange-500')
        ->and(WorkItemStatus::Resolved->color())->toContain('bg-emerald-100');
});

it('seeds the initial work item types and categories', function () {
    $this->seed(WorkItemTypeSeeder::class);

    expect(WorkItemType::query()->pluck('name')->sort()->values()->all())->toBe([
        'Desarrollo',
        'Integración',
        'Investigación',
        'Mejora',
        'Planificación',
        'Soporte',
    ])
        ->and(WorkItemType::query()->where('slug', 'development')->firstOrFail()->categories)->toHaveCount(3);
});
