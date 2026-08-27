<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ClientBackups extends Component
{
    public string $clientCode;

    public ?int $projectId = null;

    public bool $showHistory = true;

    public function mount(string $clientCode, bool $showHistory = true): void
    {
        $this->clientCode = $clientCode;
        $this->showHistory = $showHistory;
    }

    #[On('backup-connection-saved')]
    public function refreshConnections(): void {}

    public function render(ClientService $clientService): View
    {
        return view('livewire.clients.client-backups', [
            'client' => $clientService->findByCodeOrFail($this->clientCode),
        ]);
    }
}
