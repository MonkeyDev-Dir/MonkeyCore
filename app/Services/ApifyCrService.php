<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;

class ApifyCrService
{
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

        $context = [
            'cedula' => $this->maskCedula($cedula),
        ];
        $startedAt = microtime(true);

        Log::channel('apifycr')->info('Consulta de persona iniciada', $context);

        try {
            $response = $this->request()
                ->get('/cedula', [
                    'cedula' => $cedula,
                ]);
        } catch (\Throwable $exception) {
            Log::channel('apifycr')->error('Consulta de persona fallida', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if ($response->notFound()) {
            Log::channel('apifycr')->warning('Persona no encontrada', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'status' => $response->status(),
            ]);

            return null;
        }

        try {
            $response->throw();
        } catch (\Throwable $exception) {
            Log::channel('apifycr')->error('Consulta de persona fallida', $context + [
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

            Log::channel('apifycr')->error('Respuesta inválida de ApifyCR', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
                'status' => $response->status(),
            ]);

            throw $exception;
        }

        Log::channel('apifycr')->info('Consulta de persona completada', $context + [
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

        $response = $this->request()
            ->get('/juridica', [
                'cedula' => $cedula,
            ]);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        $data = $response->json();

        if (! is_array($data) || ! is_string($data['nombre'] ?? null) || trim($data['nombre']) === '') {
            throw new UnexpectedValueException('ApifyCR devolvió una respuesta jurídica sin datos válidos.');
        }

        return $data;
    }

    private function durationInMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
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
