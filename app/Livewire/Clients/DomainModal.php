<?php

namespace App\Livewire\Clients;

use App\Services\ClientService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class DomainModal extends Component
{
    public bool $isOpen = false;

    public string $clientCode = '';

    public ?int $domainId = null;

    public string $name = '';

    public string $hostingProvider = '';

    public ?string $annualCost = null;

    public string $currency = 'CRC';

    public string $expiresAt = '';

    public int $renewalPeriodYears = 1;

    public string $notes = '';

    public function openCreate(string $clientCode): void
    {
        $this->resetForm();
        $this->clientCode = $clientCode;
        $this->expiresAt = now()->addYear()->format('d/m/Y');
        $this->isOpen = true;
    }

    public function openEdit(string $clientCode, int $domainId, ClientService $clientService): void
    {
        $this->resetForm();
        $client = $clientService->findByCodeOrFail($clientCode);
        $domain = $clientService->findDomainOrFail($client, $domainId);
        $this->clientCode = $clientCode;
        $this->domainId = $domain->id;
        $this->name = $domain->name;
        $this->hostingProvider = $domain->hosting_provider ?? '';
        $this->annualCost = $domain->annual_cost;
        $this->currency = $domain->currency;
        $this->expiresAt = $domain->expires_at->format('d/m/Y');
        $this->renewalPeriodYears = $domain->renewal_period_years;
        $this->notes = $domain->notes ?? '';
        $this->resetValidation();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
    }

    public function isMigrationPending(): bool
    {
        return $this->domainId !== null && Str::lower(trim($this->hostingProvider)) !== 'dondominio';
    }

    public function save(ClientService $clientService): void
    {
        $client = $clientService->findByCodeOrFail($this->clientCode);
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('domains', 'name')->where(fn ($query) => $query->where('client_id', $client->id))->ignore($this->domainId)],
            'hostingProvider' => ['nullable', 'string', 'max:255'],
            'annualCost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['required', Rule::in(['CRC', 'USD', 'EUR'])],
            'expiresAt' => ['required', 'date_format:d/m/Y'],
            'renewalPeriodYears' => ['required', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $domain = $this->domainId === null ? null : $clientService->findDomainOrFail($client, $this->domainId);

        $clientService->saveDomain($client, [
            'name' => $validated['name'],
            'hosting_provider' => $validated['hostingProvider'] ?: null,
            'annual_cost' => $validated['annualCost'] === null || $validated['annualCost'] === ''
                ? null
                : $validated['annualCost'],
            'currency' => strtoupper($validated['currency']),
            'expires_at' => Carbon::createFromFormat('d/m/Y', $validated['expiresAt'])->toDateString(),
            'renewal_period_years' => $validated['renewalPeriodYears'],
            'notes' => $validated['notes'] ?: null,
        ], $domain);

        $wasEditing = $this->domainId !== null;
        $this->close();
        $this->dispatch('domain-saved', message: $wasEditing ? __('Dominio actualizado correctamente.') : __('Dominio creado correctamente.'));
    }

    private function resetForm(): void
    {
        $this->reset(['clientCode', 'domainId', 'name', 'hostingProvider', 'annualCost', 'currency', 'expiresAt', 'renewalPeriodYears', 'notes']);
        $this->currency = 'CRC';
        $this->renewalPeriodYears = 1;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.clients.domain-modal');
    }
}
