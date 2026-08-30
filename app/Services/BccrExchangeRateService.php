<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class BccrExchangeRateService
{
    /** @return array{date: string, dollar: array{buy: array<string, mixed>, sell: array<string, mixed>}, euro: array<string, mixed>} */
    public function obtenerTiposDeCambio(?CarbonImmutable $date = null): array
    {
        $date ??= CarbonImmutable::now();
        $formattedDate = $date->format('Y/m/d');

        return [
            'date' => $date->format('Y-m-d'),
            'dollar' => [
                'buy' => $this->obtenerIndicador((int) config('services.bccr.indicators.dollar_buy'), $formattedDate),
                'sell' => $this->obtenerIndicador((int) config('services.bccr.indicators.dollar_sell'), $formattedDate),
            ],
            'euro' => $this->obtenerIndicador((int) config('services.bccr.indicators.euro'), $formattedDate),
        ];
    }

    /** @return array{code: string, date: string, value: float} */
    private function obtenerIndicador(int $indicator, string $date): array
    {
        if ($indicator <= 0) {
            throw new InvalidArgumentException('El código del indicador BCCR debe ser positivo.');
        }

        $startedAt = microtime(true);
        $context = [
            'indicator' => $indicator,
            'date' => $date,
        ];

        Log::channel('bccr')->info('Consulta BCCR iniciada', $context);

        try {
            $response = $this->request()
                ->get($this->indicatorEndpoint($indicator), [
                    'fechaInicio' => $date,
                    'fechaFin' => $date,
                    'idioma' => 'ES',
                ]);

            $response->throw();
        } catch (\Throwable $exception) {
            $responseBody = isset($response)
                ? $response->body()
                : ($exception instanceof RequestException ? $exception->response->body() : null);

            Log::channel('bccr')->error('Consulta BCCR fallida', $context + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
                'response_body' => $responseBody,
            ]);

            throw $exception;
        }

        Log::channel('bccr')->debug('Respuesta BCCR recibida', $context + [
            'duration_ms' => $this->durationInMilliseconds($startedAt),
            'status' => $response->status(),
            'response_body' => $response->body(),
        ]);

        return $this->parseResponse($response->body(), $indicator);
    }

    private function durationInMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function indicatorEndpoint(int $indicator): string
    {
        return rtrim((string) config('services.bccr.base_url'), '/')."/indicadoresEconomicos/{$indicator}/series";
    }

    private function request(): PendingRequest
    {
        $token = config('services.bccr.token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('La consulta al BCCR requiere BCCR_TOKEN.');
        }

        $timeout = (int) config('services.bccr.timeout', 10);

        return Http::withToken($token)
            ->acceptJson()
            ->retry([200, 500], 0, function (\Throwable $exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError());
            })
            ->timeout($timeout)
            ->connectTimeout((int) config('services.bccr.connect_timeout', 5));
    }

    /** @return array{code: string, date: string, value: float} */
    private function parseResponse(string $body, int $indicator): array
    {
        $data = json_decode($body, true);
        $series = is_array($data) ? ($data['datos'][0]['series'][0] ?? null) : null;
        $code = is_array($data) ? ($data['datos'][0]['codigoIndicador'] ?? null) : null;
        $date = is_array($series) ? ($series['fecha'] ?? null) : null;
        $value = is_array($series) ? ($series['valorDatoPorPeriodo'] ?? null) : null;

        if (! is_string($code) || ! is_string($date) || ! is_numeric($value)) {
            throw new UnexpectedValueException("El BCCR no devolvió datos válidos para el indicador {$indicator}.");
        }

        return [
            'code' => $code,
            'date' => $date,
            'value' => (float) $value,
        ];
    }
}
