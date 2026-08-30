<?php

namespace App\Livewire\ApiConsumers;

use App\Models\ApiConsumer;
use App\Services\ApiConsumerService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ApiConsumerModal extends Component
{
    public bool $isOpen = false;

    public string $name = '';

    public string $tokenName = '';

    public ?int $consumerId = null;

    public string $description = '';

    public string $expiresAt = '';

    public ?string $plainTextToken = null;

    public bool $isTokenOnly = false;

    public function open(): void
    {
        $this->resetForm();
        $this->isOpen = true;
    }

    #[On('open-api-consumer-token')]
    public function openToken(int $consumerId): void
    {
        $consumer = ApiConsumer::query()->findOrFail($consumerId);
        $this->resetForm();
        $this->consumerId = $consumer->id;
        $this->name = $consumer->name;
        $this->tokenName = $consumer->name.' - token';
        $this->isTokenOnly = true;
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
    }

    public function save(ApiConsumerService $apiConsumerService): void
    {
        if ($this->isTokenOnly) {
            $this->generateToken($apiConsumerService);

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'expiresAt' => ['nullable', 'date', 'after:today'],
        ]);

        [, $token] = $apiConsumerService->createWithToken([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'active' => true,
        ], $validated['expiresAt'] ?: null);

        $this->plainTextToken = $token->plainTextToken;
        $this->dispatch('api-consumer-created');
    }

    private function generateToken(ApiConsumerService $apiConsumerService): void
    {
        $validated = $this->validate([
            'tokenName' => ['required', 'string', 'max:255'],
            'expiresAt' => ['nullable', 'date', 'after:today'],
        ]);
        $consumer = ApiConsumer::query()->findOrFail($this->consumerId);
        $token = $apiConsumerService->issueToken(
            $consumer,
            $validated['tokenName'],
            $validated['expiresAt'] ?: null,
        );

        $this->plainTextToken = $token->plainTextToken;
        $this->dispatch('api-consumer-created');
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'tokenName', 'consumerId', 'description', 'expiresAt', 'plainTextToken', 'isTokenOnly']);
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.api-consumers.api-consumer-modal');
    }
}
