<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ClientProjects extends Component
{
    public string $clientCode;

    public function mount(string $clientCode): void
    {
        $this->clientCode = $clientCode;
    }

    #[On('project-saved')]
    public function refreshProjects(): void {}

    public function render(ClientService $clientService): View
    {
        return view('livewire.clients.client-projects', [
            'client' => $clientService->findByCodeOrFail($this->clientCode),
        ]);
    }
}
