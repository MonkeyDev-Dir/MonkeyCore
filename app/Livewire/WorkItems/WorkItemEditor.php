<?php

namespace App\Livewire\WorkItems;

use App\Models\WorkItem;
use App\Services\WorkItemService;
use Illuminate\View\View;
use Livewire\Component;

class WorkItemEditor extends Component
{
    public WorkItem $workItem;

    public bool $isOpen = false;

    public ?int $workItemTypeId = null;

    public ?int $workItemCategoryId = null;

    public ?int $clientId = null;

    public ?int $projectId = null;

    public string $priority = 'medium';

    public string $status = 'new';

    public string $title = '';

    public string $description = '';

    /** @var array<int, int> */
    public array $assigneeIds = [];

    public function mount(WorkItem $workItem): void
    {
        $this->workItem = $workItem;
        $this->fillFromWorkItem();
    }

    public function openEdit(): void
    {
        $this->resetValidation();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
    }

    public function updatedWorkItemTypeId(): void
    {
        $this->workItemCategoryId = null;
    }

    public function updatedClientId(): void
    {
        $this->projectId = null;
    }

    public function saveInformation(WorkItemService $workItemService): void
    {
        $validated = $this->validate([
            'workItemTypeId' => ['required', 'integer', 'exists:work_item_types,id'],
            'workItemCategoryId' => ['nullable', 'integer', 'exists:work_item_categories,id'],
            'clientId' => ['nullable', 'integer', 'exists:clients,id'],
            'projectId' => ['nullable', 'integer', 'exists:projects,id'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'status' => ['required', 'in:new,assigned,under_analysis,waiting_for_customer,waiting_for_third_party,in_development,in_testing,resolved,closed,cancelled'],
            'title' => ['required', 'string', 'max:255'],
            'assigneeIds' => ['array'],
            'assigneeIds.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $this->persist($workItemService, $validated, $this->description);
        $this->close();
        $this->dispatch('work-item-updated', message: __('Información del caso actualizada correctamente.'));
    }

    public function saveDescription(WorkItemService $workItemService): void
    {
        $this->validate(['description' => ['nullable', 'string', 'max:10000']]);
        $this->persist($workItemService, [
            'workItemTypeId' => $this->workItemTypeId,
            'workItemCategoryId' => $this->workItemCategoryId,
            'clientId' => $this->clientId,
            'projectId' => $this->projectId,
            'priority' => $this->priority,
            'status' => $this->status,
            'title' => $this->title,
            'assigneeIds' => $this->assigneeIds,
        ], $this->description);
        $this->dispatch('work-item-updated', message: __('Descripción actualizada correctamente.'));
    }

    /** @param array<string, mixed> $validated */
    public function refreshWorkItem(WorkItemService $workItemService): void
    {
        $this->workItem = $workItemService->findByPublicCodeOrFail($this->workItem->public_code);
    }

    private function persist(WorkItemService $workItemService, array $validated, ?string $description): void
    {
        $this->workItem = $workItemService->update($this->workItem, [
            'work_item_type_id' => $validated['workItemTypeId'],
            'work_item_category_id' => $validated['workItemCategoryId'],
            'client_id' => $validated['clientId'],
            'project_id' => $validated['projectId'],
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'title' => $validated['title'],
            'description' => $description ?: null,
        ], auth()->user(), $validated['assigneeIds'] ?? []);

        $this->fillFromWorkItem();
    }

    private function fillFromWorkItem(): void
    {
        $this->workItem->loadMissing('assignees');
        $this->workItemTypeId = $this->workItem->work_item_type_id;
        $this->workItemCategoryId = $this->workItem->work_item_category_id;
        $this->clientId = $this->workItem->client_id;
        $this->projectId = $this->workItem->project_id;
        $this->priority = $this->workItem->priority->value;
        $this->status = $this->workItem->status->value;
        $this->title = $this->workItem->title;
        $this->description = (string) ($this->workItem->description ?? '');
        $this->assigneeIds = $this->workItem->assignees->modelKeys();
    }

    public function render(WorkItemService $workItemService): View
    {
        return view('livewire.work-items.work-item-editor', [
            'types' => $workItemService->types(),
            'categories' => $workItemService->categoriesForType($this->workItemTypeId),
            'clients' => $workItemService->clients(),
            'projects' => $workItemService->projectsForClient($this->clientId),
            'users' => $workItemService->users(),
            'eventLabels' => [
                'created' => __('Caso creado'),
                'assigned' => __('Responsables asignados'),
                'information_updated' => __('Información del caso modificada'),
                'description_updated' => __('Descripción modificada'),
                'assignees_updated' => __('Responsables actualizados'),
                'follow_up_created' => __('Seguimiento agregado'),
                'follow_up_updated' => __('Seguimiento modificado'),
            ],
        ]);
    }
}
