<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'type', 'identification', 'found', 'consulted_at', 'name', 'first_surname', 'second_surname',
    'father_name', 'father_identification', 'mother_name', 'mother_identification', 'electoral_code',
    'birth_date', 'expiration_date', 'province', 'canton', 'district', 'identification_type',
])]
class CivilRegistryRecord extends Model
{
    public const TypePerson = 'person';

    public const TypeJuridical = 'juridica';

    protected function casts(): array
    {
        return [
            'found' => 'boolean',
            'consulted_at' => 'immutable_datetime',
            'birth_date' => 'date:Y-m-d',
            'expiration_date' => 'date:Y-m-d',
        ];
    }

    /** @return array<string, mixed> */
    public function toPersonData(): array
    {
        return array_filter([
            'cedula' => $this->identification,
            'nombre' => $this->name,
            'apellido1' => $this->first_surname,
            'apellido2' => $this->second_surname,
            'codelec' => $this->electoral_code,
            'fecha_nacimiento' => $this->birth_date?->format('Y-m-d'),
            'fecha_caduc' => $this->expiration_date?->format('Y-m-d'),
            'provincia' => $this->province,
            'canton' => $this->canton,
            'distrito' => $this->district,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /** @return array<string, mixed> */
    public function toJuridicalData(): array
    {
        return array_filter([
            'cedula' => $this->identification,
            'nombre' => $this->name,
            'tipoIdentificacion' => $this->identification_type,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
