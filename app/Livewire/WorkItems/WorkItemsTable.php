<?php

namespace App\Livewire\WorkItems;

use App\Services\WorkItemService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class WorkItemsTable extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var array{column: string, direction: string} */
    public array $sort = [
        'column' => 'created_at',
        'direction' => 'desc',
    ];

    #[On('work-item-saved')]
    public function refreshWorkItems(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(WorkItemService $workItemService): View
    {
        return view('livewire.work-items.work-items-table', [
            'workItems' => $workItemService->paginate($this->search, $this->sort),
            'headers' => [
                ['index' => 'case', 'label' => __('Caso')],
                ['index' => 'type_name', 'label' => __('Tipo')],
                ['index' => 'status', 'label' => __('Estado')],
                ['index' => 'created_at', 'label' => __('Creado')],
            ],
        ]);
    }
}
