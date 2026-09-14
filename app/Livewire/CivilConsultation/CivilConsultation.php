<?php

namespace App\Livewire\CivilConsultation;

use App\Models\CivilRegistryRecord;
use App\Services\ApifyCrService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class CivilConsultation extends Component
{
    public string $type = CivilRegistryRecord::TypePerson;

    public string $identification = '';

    public ?array $result = null;

    public bool $searched = false;

    public function updatedType(): void
    {
        $this->reset(['identification', 'result', 'searched']);
        $this->resetValidation();
    }

    public function consult(ApifyCrService $apifyCrService): void
    {
        $this->resetErrorBag();
        $this->result = null;
        $this->searched = false;

        $validated = $this->validate([
            'type' => ['required', Rule::in([
                CivilRegistryRecord::TypePerson,
                CivilRegistryRecord::TypeJuridical,
            ])],
            'identification' => [
                'required',
                'regex:'.($this->type === CivilRegistryRecord::TypePerson ? '/^\d{9}$/' : '/^\d{10}$/'),
            ],
        ]);

        try {
            $result = $validated['type'] === CivilRegistryRecord::TypePerson
                ? $apifyCrService->consultarPersona($validated['identification'])
                : $apifyCrService->consultarJuridica($validated['identification']);
        } catch (Throwable $exception) {
            report($exception);
            $this->searched = true;
            $this->addError('lookup', __('No fue posible completar la consulta civil.'));

            return;
        }

        $this->searched = true;

        if ($result === null) {
            $this->addError('lookup', __('No se encontró información para la identificación indicada.'));

            return;
        }

        $this->result = $result;
    }

    /** @return array<int, array{label: string, value: mixed}> */
    private function resultFields(): array
    {
        if ($this->result === null) {
            return [];
        }

        $fields = $this->type === CivilRegistryRecord::TypePerson
            ? [
                ['label' => __('Cédula'), 'value' => $this->result['cedula'] ?? null],
                ['label' => __('Nombre'), 'value' => $this->result['nombre'] ?? null],
                ['label' => __('Primer apellido'), 'value' => $this->result['apellido1'] ?? $this->result['primer_apellido'] ?? null],
                ['label' => __('Segundo apellido'), 'value' => $this->result['apellido2'] ?? $this->result['segundo_apellido'] ?? null],
                ['label' => __('Código electoral'), 'value' => $this->result['codelec'] ?? null],
                ['label' => __('Fecha de nacimiento'), 'value' => $this->result['fecha_nacimiento'] ?? null],
                ['label' => __('Fecha de vencimiento'), 'value' => $this->result['fecha_caduc'] ?? $this->result['fecha_vencimiento'] ?? null],
                ['label' => __('Provincia'), 'value' => $this->result['provincia'] ?? null],
                ['label' => __('Cantón'), 'value' => $this->result['canton'] ?? null],
                ['label' => __('Distrito'), 'value' => $this->result['distrito'] ?? null],
            ]
            : [
                ['label' => __('Cédula jurídica'), 'value' => $this->result['cedula'] ?? $this->identification],
                ['label' => __('Nombre'), 'value' => $this->result['nombre'] ?? null],
                ['label' => __('Tipo de identificación'), 'value' => $this->result['tipoIdentificacion'] ?? null],
            ];

        return array_values(array_filter($fields, static fn (array $field): bool => $field['value'] !== null && $field['value'] !== ''));
    }

    public function render(): View
    {
        return view('livewire.civil-consultation.civil-consultation', [
            'resultFields' => $this->resultFields(),
        ]);
    }
}
