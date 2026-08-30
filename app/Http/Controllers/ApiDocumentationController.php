<?php

namespace App\Http\Controllers;

use Dedoc\Scramble\CacheableGenerator;
use Dedoc\Scramble\Scramble;
use Illuminate\View\View;

class ApiDocumentationController extends Controller
{
    public function exchangeRates(CacheableGenerator $generator): View
    {
        $specification = $generator(Scramble::configure());

        return view('pages.api-documentation.exchange-rates', [
            'documentation' => [
                'title' => __('Tipo de cambio'),
                'description' => __('Consulta los tipos de cambio normalizados y sincronizados desde el Banco Central de Costa Rica.'),
                'version' => 'v1',
                'baseUrl' => url('/api/v1'),
                'authentication' => __('Requiere un token Bearer de una aplicación activa registrado en API Tokens.'),
                'rateLimit' => __('120 solicitudes por minuto por token.'),
                'headers' => [
                    'Authorization: Bearer {token}',
                    'Accept: application/json',
                ],
                'endpoints' => $this->exchangeRateEndpoints($specification),
                'responses' => [
                    ['code' => 200, 'description' => __('Solicitud exitosa.')],
                    ['code' => 401, 'description' => __('Token ausente, inválido o perteneciente a una aplicación inactiva.')],
                    ['code' => 404, 'description' => __('No existen datos para la fecha solicitada.')],
                    ['code' => 429, 'description' => __('Se excedió el límite de solicitudes.')],
                ],
                'response' => <<<'JSON'
{
  "data": {
    "date": "2026-08-28",
    "source": "BCCR",
    "rates": [
      { "currency": "USD", "type": "buy", "value": 500.25 },
      { "currency": "USD", "type": "sell", "value": 507.89 }
    ],
    "updated_at": "2026-08-28T06:30:00-06:00"
  }
}
JSON,
                'examples' => [
                    'curl' => "curl --request GET \\\n+  --url {$this->documentationUrl('/api/v1/exchange-rates/latest')} \\\n+  --header 'Accept: application/json' \\\n+  --header 'Authorization: Bearer {token}'",
                    'javascript' => "const response = await fetch('{$this->documentationUrl('/api/v1/exchange-rates/latest')}', {\n  headers: {\n    Accept: 'application/json',\n    Authorization: 'Bearer {token}',\n  },\n});\n\nconst data = await response.json();",
                ],
            ],
        ]);
    }

    private function documentationUrl(string $path): string
    {
        return url($path);
    }

    /**
     * @param  array{paths?: array<string, array<string, array<string, mixed>>>}  $specification
     * @return array<int, array{method: string, path: string, description: string, parameters: array<int, array{name: string, type: string, required: bool, description: string}>}>
     */
    private function exchangeRateEndpoints(array $specification): array
    {
        $endpoints = [];

        foreach ($specification['paths'] ?? [] as $path => $operations) {
            if (! str_starts_with($path, '/exchange-rates')) {
                continue;
            }

            foreach ($operations as $method => $operation) {
                $endpoints[] = [
                    'method' => strtoupper($method),
                    'path' => $path,
                    'description' => $operation['description'] ?? __('Consulta tipos de cambio.'),
                    'parameters' => $this->endpointParameters($operation['parameters'] ?? []),
                ];
            }
        }

        return $endpoints;
    }

    /**
     * @return array<int, array{name: string, type: string, required: bool, description: string}>
     */
    private function endpointParameters(mixed $parameters): array
    {
        if (! is_array($parameters)) {
            return [];
        }

        $result = [];

        foreach ($parameters as $parameter) {
            if (! is_array($parameter)) {
                continue;
            }

            $schema = is_array($parameter['schema'] ?? null) ? $parameter['schema'] : [];

            $result[] = [
                'name' => (string) ($parameter['name'] ?? ''),
                'type' => (string) ($schema['type'] ?? 'string'),
                'required' => (bool) ($parameter['required'] ?? false),
                'description' => (string) ($parameter['description'] ?? ''),
            ];
        }

        return $result;
    }
}
