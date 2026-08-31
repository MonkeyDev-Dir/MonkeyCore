<?php

namespace App\Livewire\Clients;

use App\Services\BackupSettingsService;
use App\Services\ClientService;
use Illuminate\View\View;
use Livewire\Component;

class BackupScheduleModal extends Component
{
    public bool $isOpen = false;

    public string $clientCode = '';

    public ?int $connectionId = null;

    public bool $useCustomSchedule = false;

    public string $frequency = 'daily';

    public int $dailyRetentionMonths = 1;

    public int $monthlyRetentionYears = 3;

    public function open(string $clientCode, int $connectionId, ClientService $clientService, BackupSettingsService $settingsService): void
    {
        $client = $clientService->findByCodeOrFail($clientCode);
        $connection = $clientService->findBackupConnectionOrFail($client, $connectionId);
        $defaults = $settingsService->current();

        $this->clientCode = $clientCode;
        $this->connectionId = $connection->id;
        $this->useCustomSchedule = $connection->backup_frequency !== null;
        $this->frequency = $connection->backup_frequency ?? $defaults->frequency;
        $this->dailyRetentionMonths = $connection->backup_daily_retention_months ?? $defaults->daily_retention_months;
        $this->monthlyRetentionYears = $connection->backup_monthly_retention_years ?? $defaults->monthly_retention_years;
        $this->resetValidation();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
    }

    public function save(ClientService $clientService): void
    {
        $validated = $this->validate([
            'useCustomSchedule' => ['boolean'],
            'frequency' => ['required_if:useCustomSchedule,true', 'nullable', 'in:hourly,every_six_hours,every_twelve_hours,daily,every_two_days,weekly'],
            'dailyRetentionMonths' => ['required_if:useCustomSchedule,true', 'nullable', 'integer', 'between:1,12'],
            'monthlyRetentionYears' => ['required_if:useCustomSchedule,true', 'nullable', 'integer', 'between:1,10'],
        ]);
        $client = $clientService->findByCodeOrFail($this->clientCode);
        $connection = $clientService->findBackupConnectionOrFail($client, $this->connectionId);

        $connection->update([
            'backup_frequency' => $validated['useCustomSchedule'] ? $validated['frequency'] : null,
            'backup_daily_retention_months' => $validated['useCustomSchedule'] ? $validated['dailyRetentionMonths'] : null,
            'backup_monthly_retention_years' => $validated['useCustomSchedule'] ? $validated['monthlyRetentionYears'] : null,
        ]);

        $this->close();
        $this->dispatch('backup-schedule-saved', message: __('Configuración de frecuencia actualizada correctamente.'));
    }

    public function render(): View
    {
        return view('livewire.clients.backup-schedule-modal');
    }
}
