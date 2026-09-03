<?php

use App\Enums\WorkItemPriority;
use App\Enums\WorkItemStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItemCategory;
use App\Models\WorkItemType;
use App\Services\WorkItemService;
use Illuminate\Validation\ValidationException;

it('creates a work item with a yearly public code and an audit event', function () {
    $this->travelTo('2026-04-10 10:00:00');
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    $project->members()->attach($assignee);
    $type = WorkItemType::factory()->create(['slug' => 'support']);
    $category = WorkItemCategory::factory()->create(['work_item_type_id' => $type->id]);

    $workItem = app(WorkItemService::class)->create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'work_item_type_id' => $type->id,
        'work_item_category_id' => $category->id,
        'title' => 'Configurar respaldo del proyecto',
        'priority' => WorkItemPriority::High,
    ], $creator, [$assignee->id]);

    expect($workItem->public_code)->toBe('MKY-26000001')
        ->and($workItem->status)->toBe(WorkItemStatus::New)
        ->and($workItem->priority)->toBe(WorkItemPriority::High)
        ->and($workItem->assignees)->toHaveCount(1)
        ->and($workItem->events)->toHaveCount(2);
});

it('rejects a project that belongs to another client', function () {
    $creator = User::factory()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $otherClient->id]);
    $type = WorkItemType::factory()->create(['slug' => 'integration']);

    expect(fn () => app(WorkItemService::class)->create([
        'client_id' => $client->id,
        'project_id' => $project->id,
        'work_item_type_id' => $type->id,
        'title' => 'Integrar un servicio',
    ], $creator))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('work_items', 0);
});

it('requires client and project for support work items', function () {
    $creator = User::factory()->create();
    $type = WorkItemType::factory()->create(['slug' => 'support']);

    expect(fn () => app(WorkItemService::class)->create([
        'work_item_type_id' => $type->id,
        'title' => 'Soporte sin contexto',
    ], $creator))->toThrow(ValidationException::class);
});

it('increments the public code within a year and restarts it on the next year', function () {
    $creator = User::factory()->create();
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    $type = WorkItemType::factory()->create(['slug' => 'planning']);

    $this->travelTo('2026-12-31 23:59:00');
    $first = app(WorkItemService::class)->create([
        'work_item_type_id' => $type->id,
        'title' => 'Planificación anual',
    ], $creator);

    $this->travelTo('2027-01-01 00:01:00');
    $nextYear = app(WorkItemService::class)->create([
        'work_item_type_id' => $type->id,
        'title' => 'Planificación siguiente',
    ], $creator);

    expect($first->public_code)->toBe('MKY-26000001')
        ->and($nextYear->public_code)->toBe('MKY-27000001');
});
