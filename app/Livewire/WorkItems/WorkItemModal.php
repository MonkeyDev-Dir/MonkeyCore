<?php

namespace App\Livewire\WorkItems;

use App\Services\WorkItemService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class WorkItemModal extends Component
{
    public bool $isOpen = false;

    public ?int $workItemTypeId = null;

    public ?int $workItemCategoryId = null;

    public ?int $clientId = null;

    public ?int $projectId = null;

    public string $priority = 'medium';

    public string $title = '';

    public string $description = '';

    /** @var array<int, int> */
    public array $assigneeIds = [];

    #[On('open-work-item-create')]
    public function openCreate(): void
    {
        $this->resetForm();
        $this->isOpen = true;
    }

    public function updatedWorkItemTypeId(): void
    {
        $this->workItemCategoryId = null;
    }

    public function updatedClientId(): void
    {
        $this->projectId = null;
        $this->assigneeIds = [];
    }

    public function updatedProjectId(): void
    {
        $this->assigneeIds = [];
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
    }

    public function save(WorkItemService $workItemService): void
    {
        $validated = $this->validate([
            'workItemTypeId' => ['required', 'integer', Rule::exists('work_item_types', 'id')->where('is_active', true)],
            'workItemCategoryId' => ['nullable', 'integer', Rule::exists('work_item_categories', 'id')],
            'clientId' => ['nullable', 'integer', 'exists:clients,id'],
            'projectId' => ['nullable', 'integer', 'exists:projects,id'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'assigneeIds' => ['array'],
            'assigneeIds.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $workItemService->create([
            'work_item_type_id' => $validated['workItemTypeId'],
            'work_item_category_id' => $validated['workItemCategoryId'],
            'client_id' => $validated['clientId'],
            'project_id' => $validated['projectId'],
            'priority' => $validated['priority'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?: null,
        ], auth()->user(), $validated['assigneeIds'] ?? []);

        $this->close();
        $this->dispatch('work-item-saved', message: __('Caso creado correctamente.'));
    }

    private function resetForm(): void
    {
        $this->reset(['workItemTypeId', 'workItemCategoryId', 'clientId', 'projectId', 'title', 'description', 'assigneeIds']);
        $this->priority = 'medium';
        $this->resetValidation();
    }

    public function render(WorkItemService $workItemService): View
    {
        return view('livewire.work-items.work-item-modal', [
            'types' => $workItemService->types(),
            'categories' => $workItemTypeId = $this->workItemTypeId === null ? collect() : $workItemService->categoriesForType($this->workItemTypeId),
            'clients' => $workItemService->clients(),
            'projects' => $workItemService->projectsForClient($this->clientId),
            'users' => $workItemService->users(),
        ]);
    }
}
