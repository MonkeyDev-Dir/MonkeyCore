<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use App\Services\DatabaseBackupService;
use Illuminate\View\View;
use Livewire\Component;

class ClientAnnualBackups extends Component
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

        return view('livewire.clients.client-annual-backups', [
            'groups' => $backupService->forClientAnnualHistory($client, $this->projectId),
            'clientCode' => $this->clientCode,
        ]);
    }
}
