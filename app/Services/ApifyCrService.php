<?php

namespace App\Services;

use App\Models\CivilRegistryRecord;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;

class ApifyCrService
{
    private const CACHE_YEARS = 2;

    /**
     * @var list<string>
     */
    private const REMOVED_PERSON_FIELDS = [
        'padre',
        'cedula_padre',
        'madre',
        'cedula_madre',
    ];

    public function __construct(private GeminiService $geminiService) {}

    /**
     * Consulta los datos disponibles de una persona por su cédula costarricense.
     *
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function consultarPersona(string $cedula): ?array
    {
        if (! preg_match('/^\d{9}$/', $cedula)) {
            throw new InvalidArgumentException('La cédula debe contener exactamente 9 dígitos.');
        }

        $cachedConsultation = $this->cachedConsultation(CivilRegistryRecord::TypePerson, $cedula);

        if ($cachedConsultation !== null) {
            Log::channel('civil_registry')->info('Consulta de persona resuelta desde la base local', [
                'cedula' => $this->maskCedula($cedula),
                'found' => $cachedConsultation->found,
            ]);

            return $cachedConsultation->found
                ? $this->formatPersonNames($cachedConsultation->toPersonData())
                : null;
        }

        $context = [
            'cedula' => $this->maskCedula($cedula),
        ];
        $startedAt = microtime(true);

        Log::channel('civil_registry')->info('Consulta de persona iniciada', $context + ['provider' => 'apifycr']);

        try {
            $response = $this->request()
                ->get('/cedula', [
                    'cedula' => $cedula,
                ]);
        } catch (\Throwable $exception) {
            Log::channel('civil_registry')->error('Consulta de persona fallida', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if ($response->notFound()) {
            $this->storeNotFound(CivilRegistryRecord::TypePerson, $cedula);

            Log::channel('civil_registry')->warning('Persona no encontrada', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'status' => $response->status(),
            ]);

            return null;
        }

        try {
            $response->throw();
        } catch (\Throwable $exception) {
            Log::channel('civil_registry')->error('Consulta de persona fallida', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
                'status' => $response->status(),
            ]);

            throw $exception;
        }

        $data = $response->json();

        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        if (! is_array($data)) {
            $exception = new UnexpectedValueException('ApifyCR devolvió una respuesta sin datos válidos.');

            Log::channel('civil_registry')->error('Respuesta inválida de ApifyCR', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
                'status' => $response->status(),
            ]);

            throw $exception;
        }

        try {
            $data = $this->formatPersonNames($data);
        } catch (\Throwable $exception) {
            Log::channel('civil_registry')->error('Corrección de nombres de la persona fallida', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'stage' => 'name_formatting',
                'response_fields' => array_keys($data),
            ] + $this->exceptionContext($exception));

            throw $exception;
        }

        try {
            $this->storePerson($cedula, $data);
        } catch (\Throwable $exception) {
            Log::channel('civil_registry')->error('Almacenamiento de la consulta de persona fallido', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'stage' => 'cache_persistence',
                'response_fields' => array_keys($data),
            ] + $this->exceptionContext($exception));

            throw $exception;
        }

        $data = Arr::except($data, self::REMOVED_PERSON_FIELDS);

        Log::channel('civil_registry')->info('Consulta de persona completada', $context + [
            'duration_ms' => $this->durationInMilliseconds($startedAt),
            'status' => $response->status(),
            'fields' => array_keys($data),
        ]);

        return $data;
    }

    /**
     * Consulta los datos disponibles de una persona jurídica por su cédula jurídica.
     *
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function consultarJuridica(string $cedula): ?array
    {
        if (! preg_match('/^\d{10}$/', $cedula)) {
            throw new InvalidArgumentException('La cédula jurídica debe contener exactamente 10 dígitos.');
        }

        $cachedConsultation = $this->cachedConsultation(CivilRegistryRecord::TypeJuridical, $cedula);

        if ($cachedConsultation !== null) {
            Log::channel('civil_registry')->info('Consulta jurídica resuelta desde la base local', [
                'cedula' => $this->maskCedula($cedula),
                'found' => $cachedConsultation->found,
            ]);

            if (! $cachedConsultation->found) {
                return null;
            }

            $data = $cachedConsultation->toJuridicalData();
            $data['nombre'] = $this->formatName((string) $cachedConsultation->name);

            return $data;
        }

        Log::channel('civil_registry')->info('Consulta jurídica iniciada', [
            'cedula' => $this->maskCedula($cedula),
            'provider' => 'apifycr',
        ]);

        $response = $this->request()
            ->get('/juridica', [
                'cedula' => $cedula,
            ]);

        if ($response->notFound()) {
            $this->storeNotFound(CivilRegistryRecord::TypeJuridical, $cedula);

            Log::channel('civil_registry')->warning('Entidad jurídica no encontrada', [
                'cedula' => $this->maskCedula($cedula),
                'status' => $response->status(),
            ]);

            return null;
        }

        $response->throw();

        $data = $response->json();

        if (! is_array($data) || ! is_string($data['nombre'] ?? null) || trim($data['nombre']) === '') {
            throw new UnexpectedValueException('ApifyCR devolvió una respuesta jurídica sin datos válidos.');
        }

        $data['nombre'] = $this->formatName($data['nombre']);
        $this->storeJuridical($cedula, $data);

        Log::channel('civil_registry')->info('Consulta jurídica completada', [
            'cedula' => $this->maskCedula($cedula),
            'status' => $response->status(),
            'fields' => array_keys($data),
        ]);

        return $data;
    }

    private function cachedConsultation(string $type, string $identification): ?CivilRegistryRecord
    {
        return CivilRegistryRecord::query()
            ->where('type', $type)
            ->where('identification', $identification)
            ->where('consulted_at', '>=', CarbonImmutable::now()->subYears(self::CACHE_YEARS))
            ->first();
    }

    /** @param array<string, mixed> $data */
    private function formatPersonNames(array $data): array
    {
        foreach (['nombre', 'apellido1', 'primer_apellido', 'apellido2', 'segundo_apellido'] as $field) {
            if (is_string($data[$field] ?? null) && trim($data[$field]) !== '') {
                $data[$field] = $this->formatName($data[$field]);
            }
        }

        return $data;
    }

    private function formatName(string $name): string
    {
        return $this->geminiService->corregirNombreApellido($name);
    }

    /** @param array<string, mixed> $data */
    private function storePerson(string $identification, array $data): void
    {
        $this->store(CivilRegistryRecord::TypePerson, $identification, [
            'name' => $this->stringValue($data['nombre'] ?? null),
            'first_surname' => $this->stringValue($data['apellido1'] ?? $data['primer_apellido'] ?? null),
            'second_surname' => $this->stringValue($data['apellido2'] ?? $data['segundo_apellido'] ?? null),
            'father_name' => $this->stringValue($data['padre'] ?? null),
            'father_identification' => $this->stringValue($data['cedula_padre'] ?? null),
            'mother_name' => $this->stringValue($data['madre'] ?? null),
            'mother_identification' => $this->stringValue($data['cedula_madre'] ?? null),
            'electoral_code' => $this->stringValue($data['codelec'] ?? null),
            'birth_date' => $this->dateValue($data['fecha_nacimiento'] ?? null),
            'expiration_date' => $this->dateValue($data['fecha_caduc'] ?? $data['fecha_vencimiento'] ?? null),
            'province' => $this->stringValue($data['provincia'] ?? null),
            'canton' => $this->stringValue($data['canton'] ?? null),
            'district' => $this->stringValue($data['distrito'] ?? null),
            'identification_type' => null,
        ]);
    }

    /** @param array<string, mixed> $data */
    private function storeJuridical(string $identification, array $data): void
    {
        $this->store(CivilRegistryRecord::TypeJuridical, $identification, [
            'name' => trim((string) $data['nombre']),
            'first_surname' => null,
            'second_surname' => null,
            'father_name' => null,
            'father_identification' => null,
            'mother_name' => null,
            'mother_identification' => null,
            'electoral_code' => null,
            'birth_date' => null,
            'expiration_date' => null,
            'province' => null,
            'canton' => null,
            'district' => null,
            'identification_type' => $this->stringValue($data['tipoIdentificacion'] ?? null),
        ]);
    }

    private function storeNotFound(string $type, string $identification): void
    {
        $this->store($type, $identification, [
            'name' => null,
            'first_surname' => null,
            'second_surname' => null,
            'father_name' => null,
            'father_identification' => null,
            'mother_name' => null,
            'mother_identification' => null,
            'electoral_code' => null,
            'birth_date' => null,
            'expiration_date' => null,
            'province' => null,
            'canton' => null,
            'district' => null,
            'identification_type' => null,
        ], false);
    }

    /** @param array<string, mixed> $attributes */
    private function store(string $type, string $identification, array $attributes, bool $found = true): void
    {
        CivilRegistryRecord::query()->updateOrCreate(
            ['type' => $type, 'identification' => $identification],
            $attributes + ['found' => $found, 'consulted_at' => now()],
        );
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function dateValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function durationInMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    /** @return array{exception: class-string<\Throwable>, error: string, cause_exception: class-string<\Throwable>|null, cause_error: string|null} */
    private function exceptionContext(\Throwable $exception): array
    {
        $cause = $exception->getPrevious();

        return [
            'exception' => $exception::class,
            'error' => $exception->getMessage(),
            'cause_exception' => $cause === null ? null : $cause::class,
            'cause_error' => $cause?->getMessage(),
        ];
    }

    private function maskCedula(string $cedula): string
    {
        return substr($cedula, 0, 1).'******'.substr($cedula, -2);
    }

    private function request(): PendingRequest
    {
        $apiKey = config('services.apifycr.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new UnexpectedValueException('La integración con ApifyCR no está configurada.');
        }

        return Http::baseUrl((string) config('services.apifycr.base_url'))
            ->withToken($apiKey)
            ->acceptJson()
            ->retry([200, 500], function (int $attempt, \Throwable $exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError());
            })
            ->timeout((int) config('services.apifycr.timeout', 8))
            ->connectTimeout((int) config('services.apifycr.connect_timeout', 3));
    }
}
