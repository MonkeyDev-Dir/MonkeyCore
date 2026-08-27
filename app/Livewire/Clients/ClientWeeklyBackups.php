<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use App\Services\DatabaseBackupService;
use Illuminate\View\View;
use Livewire\Component;

class ClientWeeklyBackups extends Component
{
    public string $clientCode;

    public ?int $projectId = null;

    public function mount(string $clientCode, ?int $projectId = null): void
    {
        $this->clientCode = $clientCode;
        $this->projectId = $projectId;
    }

    public function render(ClientService $clientService, DatabaseBackupService $backupService): View
    {
        $client = $clientService->findByCodeOrFail($this->clientCode);

        return view('livewire.clients.client-weekly-backups', [
            'groups' => $backupService->forClientCurrentWeek($client, $this->projectId),
            'clientCode' => $this->clientCode,
            'headers' => [
                ['index' => 'filename', 'label' => __('Archivo')],
                ['index' => 'connection', 'label' => __('Conexión')],
                ['index' => 'generated_at', 'label' => __('Hora')],
                ['index' => 'size', 'label' => __('Tamaño')],
                ['index' => 'action', 'label' => ''],
            ],
        ]);
    }
}
