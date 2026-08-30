<?php

namespace App\Livewire\ApiConsumers;

use App\Models\ApiConsumer;
use App\Services\ApiConsumerService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

class ApiConsumersTable extends Component
{
    use Interactions;

    public string $search = '';

    #[On('api-consumer-created')]
    #[On('api-consumer-deleted')]
    public function refreshConsumers(): void {}

    public function revokeToken(int $consumerId, int $tokenId, ApiConsumerService $apiConsumerService): void
    {
        $consumer = ApiConsumer::query()->findOrFail($consumerId);
        $apiConsumerService->revokeToken($consumer, $tokenId);

        $this->toast()
            ->success(__('Token revocado'))
            ->send();
    }

    public function deactivate(int $consumerId, ApiConsumerService $apiConsumerService): void
    {
        $consumer = ApiConsumer::query()->findOrFail($consumerId);
        $apiConsumerService->deactivate($consumer);

        $this->toast()
            ->success(__('Consumidor desactivado'))
            ->send();
    }

    public function deleteConsumer(int $consumerId, ApiConsumerService $apiConsumerService): void
    {
        $consumer = ApiConsumer::query()->findOrFail($consumerId);
        $apiConsumerService->delete($consumer);

        $this->toast()
            ->success(__('Consumidor eliminado'))
            ->send();
    }

    public function render(ApiConsumerService $apiConsumerService): View
    {
        return view('livewire.api-consumers.api-consumers-table', [
            'consumers' => $apiConsumerService->all($this->search),
        ]);
    }
}
