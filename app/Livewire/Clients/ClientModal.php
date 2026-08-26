<?php

namespace App\Livewire\Clients;

use App\Services\ApifyCrService;
use App\Services\ClientService;
use App\Services\GeminiService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class ClientModal extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public ?int $clientId = null;

    public string $type = 'company';

    public string $name = '';

    public string $legalName = '';

    public string $taxId = '';

    public string $email = '';

    public string $phone = '';

    public string $website = '';

    public string $details = '';

    public mixed $image = null;

    public string $contactName = '';

    public string $contactPosition = '';

    public string $contactEmail = '';

    public string $contactPhone = '';

    public string $contactMobilePhone = '';

    public string $addressLine = '';

    public string $city = '';

    public string $state = '';

    public string $country = '';

    public string $postalCode = '';

    #[On('open-client-create')]
    public function openCreate(): void
    {
        $this->resetForm();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
    }

    public function lookupPerson(ApifyCrService $apifyCrService, GeminiService $geminiService): void
    {
        $this->resetErrorBag(['taxId', 'lookup']);

        $this->validateOnly('taxId', [
            'taxId' => ['required', 'regex:/^\\d{9}$/'],
        ]);

        try {
            $person = $apifyCrService->consultarPersona($this->taxId);
        } catch (Throwable $exception) {
            report($exception);

            $this->addError('lookup', __('No fue posible consultar la información de la cédula.'));

            return;
        }

        if ($person === null) {
            $this->addError('lookup', __('No se encontró información para la cédula indicada.'));

            return;
        }

        $this->taxId = (string) ($person['cedula'] ?? $this->taxId);
        $name = trim(implode(' ', array_filter([
            $person['nombre'] ?? null,
            $person['primer_apellido'] ?? $person['apellido1'] ?? $person['apellido'] ?? null,
            $person['segundo_apellido'] ?? $person['apellido2'] ?? null,
        ], static fn (mixed $value): bool => is_string($value) && trim($value) !== '')));

        $this->name = $this->correctName($geminiService, $name);
    }

    public function lookupCompany(ApifyCrService $apifyCrService, GeminiService $geminiService): void
    {
        $this->resetErrorBag(['taxId', 'lookup']);

        $this->validateOnly('taxId', [
            'taxId' => ['required', 'regex:/^\d{10}$/'],
        ]);

        try {
            $company = $apifyCrService->consultarJuridica($this->taxId);
        } catch (Throwable $exception) {
            report($exception);

            $this->addError('lookup', __('No fue posible consultar la información de la cédula jurídica.'));

            return;
        }

        if ($company === null) {
            $this->addError('lookup', __('No se encontró información para la cédula jurídica indicada.'));

            return;
        }

        $this->taxId = (string) ($company['cedula'] ?? $this->taxId);
        $this->name = $this->correctName($geminiService, trim((string) $company['nombre']));
    }

    private function correctName(GeminiService $geminiService, string $name): string
    {
        try {
            return $geminiService->corregirNombreApellido($name);
        } catch (Throwable $exception) {
            report($exception);

            return $name;
        }
    }

    public function save(ClientService $clientService): void
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(['person', 'company'])],
            'name' => ['required', 'string', 'max:255'],
            'legalName' => ['nullable', 'string', 'max:255'],
            'taxId' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'details' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'contactName' => ['nullable', 'string', 'max:255'],
            'contactPosition' => ['nullable', 'string', 'max:255'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'contactPhone' => ['nullable', 'string', 'max:50'],
            'contactMobilePhone' => ['nullable', 'string', 'max:50'],
            'addressLine' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postalCode' => ['nullable', 'string', 'max:30'],
        ]);

        $clientService->save([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'legal_name' => $validated['legalName'] ?: null,
            'tax_id' => $validated['taxId'] ?: null,
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'website' => $validated['website'] ?: null,
            'details' => $validated['details'] ?: null,
            'created_by' => auth()->id(),
            'contact' => [
                'name' => $validated['contactName'],
                'position' => $validated['contactPosition'],
                'email' => $validated['contactEmail'],
                'phone' => $validated['contactPhone'],
                'mobile_phone' => $validated['contactMobilePhone'],
            ],
            'address' => [
                'address_line' => $validated['addressLine'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'country' => $validated['country'],
                'postal_code' => $validated['postalCode'],
            ],
        ], null, $this->image);

        $this->close();
        $this->dispatch('client-saved', message: __('Cliente creado correctamente.'));
    }

    private function resetForm(): void
    {
        $this->reset([
            'clientId', 'type', 'name', 'legalName', 'taxId', 'email', 'phone', 'website', 'details', 'image',
            'contactName', 'contactPosition', 'contactEmail', 'contactPhone', 'contactMobilePhone',
            'addressLine', 'city', 'state', 'country', 'postalCode',
        ]);
        $this->type = 'company';
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.clients.client-modal');
    }
}
