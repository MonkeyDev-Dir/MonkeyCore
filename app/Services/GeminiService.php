<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;

class GeminiService
{
    /**
     * Corrige la ortografía de un nombre o apellido y conserva una palabra por cada segmento.
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

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $correctedName = $this->toTitleCase($this->requestCorrection($nombreApellido));

                Log::channel('civil_registry')->info('Corrección de nombre completada con Gemini', [
                    'attempt' => $attempt,
                    'characters' => mb_strlen($nombreApellido),
                ]);

                return $correctedName;
            } catch (\Throwable $exception) {
                Log::channel('civil_registry')->warning('Intento de corrección de nombre fallido', [
                    'attempt' => $attempt,
                    'characters' => mb_strlen($nombreApellido),
                    'exception' => $exception::class,
                    'error' => $this->safeErrorMessage($exception),
                ]);

                if ($attempt === 3) {
                    Log::channel('civil_registry')->error('Corrección de nombre agotó los intentos; se aplicó formato local', [
                        'attempts' => 3,
                        'characters' => mb_strlen($nombreApellido),
                    ]);

                    return $this->toTitleCase($nombreApellido);
                }
            }
        }

        return $this->toTitleCase($nombreApellido);
    }

    private function requestCorrection(string $nombreApellido): string
    {
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

        return $data['name'];
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

    private function safeErrorMessage(\Throwable $exception): string
    {
        $apiKey = config('services.gemini.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return $exception->getMessage();
        }

        return str_replace($apiKey, '[REDACTED]', $exception->getMessage());
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
            ->timeout($timeout)
            ->connectTimeout(min($timeout, 5));
    }
}
