<?php

use App\Livewire\WorkItems\WorkItemEditor;
use App\Livewire\WorkItems\WorkItemFollowUpComposer;
use App\Livewire\WorkItems\WorkItemModal;
use App\Livewire\WorkItems\WorkItemsTable;
use App\Models\Client;
use App\Models\FileType;
use App\Models\Project;
use App\Models\StoredFile;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemFollowUp;
use App\Models\WorkItemType;
use App\Services\WorkItemService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('renders the work desk for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('work-items.index'))
        ->assertOk()
        ->assertViewIs('pages.work-items')
        ->assertSee(__('Mesa de trabajo'))
        ->assertSee(__('Casos creados'))
        ->assertSee(__('Nuevo caso'));
});

it('renders created work items in the work desk', function () {
    $user = User::factory()->create();
    $type = WorkItemType::factory()->create(['name' => 'Desarrollo']);

    app(WorkItemService::class)->create([
        'work_item_type_id' => $type->id,
        'title' => 'Preparar nueva integración',
        'description' => 'Definir el alcance inicial.',
    ], $user);

    Livewire::actingAs($user)
        ->test(WorkItemsTable::class)
        ->assertSee('Preparar nueva integración')
        ->assertSee('Interno')
        ->assertDontSee('Prioridad: medium')
        ->assertSee('MKY-'.now()->format('y').'000001');
});
it('searches and sorts work items from the work desk', function () {
    $user = User::factory()->create();
    $type = WorkItemType::factory()->create(['name' => 'Desarrollo']);
    $client = Client::factory()->create(['name' => 'Empresa Acme']);

    app(WorkItemService::class)->create([
        'work_item_type_id' => $type->id,
        'client_id' => $client->id,
        'title' => 'Caso de integración',
    ], $user);
    app(WorkItemService::class)->create([
        'work_item_type_id' => $type->id,
        'title' => 'Caso interno',
    ], $user);

    Livewire::actingAs($user)
        ->test(WorkItemsTable::class)
        ->set('search', 'Acme')
        ->assertSee('Caso de integración')
        ->assertDontSee('Caso interno')
        ->set('search', '')
        ->set('sort', ['column' => 'case', 'direction' => 'asc'])
        ->assertSet('sort', ['column' => 'case', 'direction' => 'asc'])
        ->assertSee('Caso de integración')
        ->assertSee('Caso interno');
});
it('shows a work item detail', function () {
    $user = User::factory()->create();
    $type = WorkItemType::factory()->create(['name' => 'Desarrollo']);

    $workItem = app(WorkItemService::class)->create([
        'work_item_type_id' => $type->id,
        'title' => 'Revisar el detalle del caso',
        'description' => 'Descripción del caso.',
    ], $user);

    $this->actingAs($user)
        ->get(route('work-items.show', ['publicCode' => $workItem->public_code]))
        ->assertOk()
        ->assertViewIs('pages.work-item-show')
        ->assertSee('Revisar el detalle del caso')
        ->assertSee(__('Descripción'))
        ->assertSee(__('Ver historial'))
        ->assertSee('work-item-history-modal')
        ->assertSee(__('Caso creado'))
        ->assertSee(__('Nuevo'));
});
it('adds a follow-up and associates uploaded images with it', function () {
    Storage::fake('s3');
    $user = User::factory()->create();
    $type = WorkItemType::factory()->create(['name' => 'Desarrollo']);
    $fileType = FileType::query()->create(['key' => FileType::WorkItemFollowUpImage, 'name' => 'Imagen de seguimiento']);
    $workItem = app(WorkItemService::class)->create([
        'work_item_type_id' => $type->id,
        'title' => 'Seguimiento del caso',
    ], $user);

    $storedFile = app(WorkItemService::class)->storeFollowUpImage(
        $workItem,
        UploadedFile::fake()->image('avance.png', 120, 80),
        $user,
    );

    Livewire::actingAs($user)
        ->test(WorkItemFollowUpComposer::class, ['workItem' => $workItem])
        ->assertSee(__('Agregar seguimiento'))
        ->assertSee(__('Cargando información del seguimiento'))
        ->assertSee('paste.capture')
        ->assertSee(__('Horas efectivas (opcional)'))
        ->set('effectiveHours', 2.5)
        ->set('content', '<p>Se completó la primera etapa.</p><pre><code>deploy --staging</code></pre>')
        ->set('pendingAttachmentIds', [$storedFile->id])
        ->call('save')
        ->assertDispatched('work-item-follow-up-saved')
        ->assertSee('1 actualización')
        ->assertSee('2.50 h efectivas');

    $followUp = WorkItemFollowUp::query()->firstOrFail();

    expect($followUp->content)->toContain('deploy --staging')
        ->and(StoredFile::query()->findOrFail($storedFile->id)->work_item_follow_up_id)->toBe($followUp->id)
        ->and($fileType->id)->toBe($storedFile->file_type_id);

    Livewire::actingAs($user)
        ->test(WorkItemFollowUpComposer::class, ['workItem' => $workItem])
        ->call('openEdit', $followUp->id)
        ->set('content', '<p>Se actualizó el avance.</p>')
        ->set('effectiveHours', 3.25)
        ->call('save')
        ->assertSee('Se actualizó el avance.');

    $followUp->refresh();

    expect((float) $followUp->effective_hours)->toBe(3.25)
        ->and($followUp->content)->toContain('Se actualizó el avance.')
        ->and($workItem->events()->whereIn('event_type', ['follow_up_created', 'follow_up_updated'])->pluck('event_type')->all())
        ->toBe(['follow_up_created', 'follow_up_updated']);

    Storage::disk('s3')->assertExists($storedFile->path);
});
it('updates a work item and records each change', function () {
    $user = User::factory()->create();
    $assignee = User::factory()->create();
    $type = WorkItemType::factory()->create(['name' => 'Desarrollo']);

    $workItem = app(WorkItemService::class)->create([
        'work_item_type_id' => $type->id,
        'title' => 'Título original',
        'description' => 'Descripción original',
    ], $user);

    Livewire::actingAs($user)
        ->test(WorkItemEditor::class, ['workItem' => $workItem])
        ->assertSee('open.modal')
        ->assertSee('openEdit')
        ->assertSee('Buscar responsable...')
        ->assertSee($assignee->name.' '.$assignee->lastname)
        ->set('title', 'Título actualizado')
        ->set('description', 'Descripción actualizada')
        ->set('priority', 'high')
        ->set('status', 'assigned')
        ->set('assigneeIds', [$assignee->id])
        ->call('saveInformation')
        ->assertDispatched('work-item-updated');

    $workItem->refresh();

    expect($workItem->title)->toBe('Título actualizado')
        ->and($workItem->description)->toBe('Descripción actualizada')
        ->and($workItem->priority->value)->toBe('high')
        ->and($workItem->status->value)->toBe('assigned')
        ->and($workItem->assignees()->whereKey($assignee->id)->exists())->toBeTrue();

    expect($workItem->events()->whereIn('event_type', ['information_updated', 'description_updated', 'assignees_updated'])->pluck('event_type')->all())
        ->toBe(['information_updated', 'description_updated', 'assignees_updated']);
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

it('renders a searchable assignee selector for system users', function () {
    $user = User::factory()->create(['name' => 'Ana', 'lastname' => 'García']);
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    $project->members()->attach($user);

    Livewire::actingAs($user)
        ->test(WorkItemModal::class)
        ->set('projectId', $project->id)
        ->assertSee('Buscar responsable...')
        ->assertSee('Ana García')
        ->assertDontSee('José Inactivo');
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
