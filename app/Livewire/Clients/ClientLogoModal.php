<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class ClientLogoModal extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public ?string $clientCode = null;

    public ?string $currentImageUrl = null;

    public mixed $image = null;

    public function open(string $clientCode, ClientService $clientService): void
    {
        $this->resetValidation();
        $this->reset('image');
        $this->clientCode = $clientCode;
        $this->currentImageUrl = $clientService->findByCodeOrFail($clientCode)->imageUrl();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
        $this->reset(['image', 'currentImageUrl']);
    }

    public function save(ClientService $clientService): void
    {
        $validated = $this->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        $client = $clientService->findByCodeOrFail((string) $this->clientCode);
        $client = $clientService->updateLogo($client, $validated['image']);

        $this->close();
        $this->dispatch('client-logo-updated', url: $client->imageUrl(), message: __('Logo actualizado correctamente.'));
    }

    public function render(): View
    {
        return view('livewire.clients.client-logo-modal');
    }
}
