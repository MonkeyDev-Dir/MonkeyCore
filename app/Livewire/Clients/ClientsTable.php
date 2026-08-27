<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ClientsTable extends Component
{
    #[On('client-saved')]
    public function refreshClients(): void {}

    public function render(ClientService $clientService): View
    {
        return view('livewire.clients.clients-table', [
            'clients' => $clientService->all(),
            'headers' => [
                ['index' => 'name', 'label' => __('Cliente')],
                ['index' => 'type', 'label' => __('Tipo')],
                ['index' => 'contact', 'label' => __('Contacto principal')],
                ['index' => 'email', 'label' => __('Correo electrónico')],
            ],
        ]);
    }
}
