<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ClientDomains extends Component
{
    public string $clientCode;

    public function mount(string $clientCode): void
    {
        $this->clientCode = $clientCode;
    }

    #[On('domain-saved')]
    public function refreshDomains(): void {}

    public function render(ClientService $clientService): View
    {
        return view('livewire.clients.client-domains', [
            'client' => $clientService->findByCodeOrFail($this->clientCode),
        ]);
    }
}
