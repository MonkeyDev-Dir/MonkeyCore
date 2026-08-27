<?php

namespace App\Livewire\Backups;

use App\Services\DatabaseBackupService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class BackupsTable extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(DatabaseBackupService $backupService): View
    {
        return view('livewire.backups.backups-table', [
            'backups' => $backupService->paginateProcessed($this->search),
            'headers' => [
                ['index' => 'name', 'label' => __('Archivo')],
                ['index' => 'database_type', 'label' => __('Tipo de base de datos')],
                ['index' => 'extension', 'label' => __('Extensión')],
                ['index' => 'last_modified', 'label' => __('Fecha')],
                ['index' => 'size', 'label' => __('Tamaño')],
                ['index' => 'action', 'label' => ''],
            ],
        ]);
    }
}
