<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ClientsTable extends Component
{
    use WithPagination;

    public string $search = '';

    #[On('client-saved')]
    public function refreshClients(): void {}

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(ClientService $clientService): View
    {
        return view('livewire.clients.clients-table', [
            'clients' => $clientService->paginate($this->search),
            'headers' => [
                ['index' => 'name', 'label' => __('Cliente')],
                ['index' => 'type', 'label' => __('Tipo')],
                ['index' => 'contact', 'label' => __('Contacto principal')],
                ['index' => 'email', 'label' => __('Correo electrónico')],
            ],
        ]);
    }
}
