<?php

namespace App\Livewire\Clients;

use App\Models\DatabaseBackup;
use App\Services\ClientService;
use App\Services\DatabaseBackupService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

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

    public function queueBackup(int $connectionId, ClientService $clientService, DatabaseBackupService $backupService): void
    {
        $client = $clientService->findByCodeOrFail($this->clientCode);
        $connection = $clientService->findBackupConnectionOrFail($client, $connectionId);

        if (! $connection->is_active) {
            $this->dispatch('backup-queue-failed', message: __('La configuración de respaldo está inactiva.'));

            return;
        }

        if (DatabaseBackup::query()
            ->where('backup_connection_id', $connection->id)
            ->whereIn('status', ['queued', 'running'])
            ->exists()) {
            $this->dispatch('backup-queue-warning', message: __('Ya existe un respaldo en proceso para esta configuración.'));

            return;
        }

        try {
            $backupService->queueForConnection($connection);
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatch('backup-queue-failed', message: __('No fue posible encolar el respaldo. Revisa los logs para más información.'));

            return;
        }

        $this->dispatch('backup-queued', message: __('El respaldo fue encolado correctamente.'));
    }

    public function render(ClientService $clientService): View
    {
        return view('livewire.clients.client-backups', [
            'client' => $clientService->findByCodeOrFail($this->clientCode),
        ]);
    }
}
