<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use UnexpectedValueException;

class GeminiService
{
    /**
     * Corrige la ortografía de un nombre o apellido y conserva una palabra por cada segmento.
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function corregirNombreApellido(string $nombreApellido): string
    {
        $nombreApellido = trim($nombreApellido);

        if ($nombreApellido === '') {
            throw new InvalidArgumentException('El nombre y apellido no pueden estar vacíos.');
        }

        if (! config('services.gemini.enabled', false)) {
            return $this->toTitleCase($nombreApellido);
        }

        $response = $this->request()->post(
            '/v1beta/models/'.config('services.gemini.model').':generateContent',
            [
                'contents' => [[
                    'parts' => [[
                        'text' => "Corrige únicamente la ortografía, tildes y capitalización del siguiente nombre o apellido. No agregues ni elimines palabras ni espacios. Devuelve las palabras separadas por espacios y responde exclusivamente como JSON válido con la clave `name`. Nombre o apellido: {$nombreApellido}",
                    ]],
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ],
        );

        $response->throw();

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new UnexpectedValueException('Gemini devolvió una respuesta sin texto válido.');
        }

        $data = json_decode(trim($text), true);

        if (! is_array($data) || ! isset($data['name']) || ! is_string($data['name'])) {
            throw new UnexpectedValueException('Gemini devolvió un nombre en un formato inválido.');
        }

        return $this->toTitleCase($data['name']);
    }

    private function toTitleCase(string $value): string
    {
        $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || $words === []) {
            return '';
        }

        return implode(' ', array_map(
            fn (string $word): string => mb_strtoupper(mb_substr(mb_strtolower($word), 0, 1)).mb_substr(mb_strtolower($word), 1),
            $words,
        ));
    }

    private function request(): PendingRequest
    {
        $apiKey = config('services.gemini.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new UnexpectedValueException('La integración con Gemini no está configurada.');
        }

        $timeout = (int) config('services.gemini.timeout', 30);

        return Http::baseUrl((string) config('services.gemini.base_url'))
            ->withQueryParameters(['key' => $apiKey])
            ->acceptJson()
            ->retry([200, 500], function (int $attempt, \Throwable $exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError());
            })
            ->timeout($timeout)
            ->connectTimeout(min($timeout, 5));
    }
}
