<?php

use App\Livewire\WorkItems\WorkItemModal;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemType;
use Livewire\Livewire;

it('renders the work desk for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('work-items.index'))
        ->assertOk()
        ->assertViewIs('pages.work-items')
        ->assertSee(__('Mesa de trabajo'))
        ->assertSee(__('La Mesa de trabajo está lista para comenzar'))
        ->assertSee(__('Nuevo caso'));
});

it('redirects guests from the work desk', function () {
    $this->get(route('work-items.index'))
        ->assertRedirect(route('login'));
});

it('opens the create work item modal', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(WorkItemModal::class)
        ->call('openCreate')
        ->assertSet('isOpen', true)
        ->assertSee(__('Nuevo caso'));
});

it('creates a work item from the create modal', function () {
    $user = User::factory()->create();
    $type = WorkItemType::factory()->create(['slug' => 'development']);
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);

    Livewire::actingAs($user)
        ->test(WorkItemModal::class)
        ->call('openCreate')
        ->fill([
            'workItemTypeId' => $type->id,
            'clientId' => $client->id,
            'projectId' => $project->id,
            'priority' => 'high',
            'title' => 'Preparar nueva integración',
            'description' => 'Definir el alcance inicial.',
        ])
        ->call('save')
        ->assertSet('isOpen', false)
        ->assertDispatched('work-item-saved');

    $workItem = WorkItem::query()->where('title', 'Preparar nueva integración')->firstOrFail();

    expect($workItem->public_code)->toBe('MKY-'.now()->format('y').'000001')
        ->and($workItem->priority->value)->toBe('high')
        ->and($workItem->project_id)->toBe($project->id);
});
