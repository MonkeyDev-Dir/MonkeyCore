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

    public function civilRegistry(): View
    {
        return view('pages.api-documentation.civil-registry', [
            'documentation' => [
                'title' => __('Consulta civil'),
                'description' => __('Consulta personas y entidades jurídicas desde el registro civil centralizado del Core.'),
                'version' => 'v1',
                'baseUrl' => url('/api/v1'),
                'authentication' => __('Requiere un token Bearer de una aplicación activa registrado en API Tokens.'),
                'rateLimit' => __('120 solicitudes por minuto por token.'),
                'headers' => [
                    'Authorization: Bearer {token}',
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
                'parameterTitle' => __('Cuerpo de la solicitud'),
                'behavior' => [
                    __('El Core consulta primero el registro local y solo consulta el proveedor externo cuando el registro no existe o tiene más de dos años.'),
                    __('Los nombres y apellidos se corrigen con Gemini hasta un máximo de tres intentos.'),
                    __('Si Gemini no responde correctamente, se aplica formato de nombre propio como respaldo.'),
                    __('La respuesta no incluye información de padre ni madre.'),
                ],
                'endpoints' => [
                    [
                        'method' => 'POST',
                        'path' => '/civil-registry/people',
                        'description' => __('Consulta la información de una persona por su número de cédula.'),
                        'parameters' => [[
                            'name' => 'cedula',
                            'type' => 'string',
                            'required' => true,
                            'description' => __('Número de cédula física de 9 dígitos.'),
                        ]],
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/civil-registry/legal-entities',
                        'description' => __('Consulta la información de una entidad jurídica por su número de cédula.'),
                        'parameters' => [[
                            'name' => 'cedula',
                            'type' => 'string',
                            'required' => true,
                            'description' => __('Número de cédula jurídica de 10 dígitos.'),
                        ]],
                    ],
                ],
                'responses' => [
                    ['code' => 200, 'description' => __('Solicitud exitosa. La respuesta proviene del registro local o de una sincronización con el proveedor externo.')],
                    ['code' => 401, 'description' => __('Token ausente, inválido o perteneciente a una aplicación inactiva.')],
                    ['code' => 404, 'description' => __('No existe información para la cédula solicitada.')],
                    ['code' => 429, 'description' => __('Se excedió el límite de solicitudes.')],
                ],
                'response' => <<<'JSON'
{
  "data": {
    "cedula": "113420689",
    "nombre": "GILBERTH ANDRES",
    "tipo": "person",
    "found": true,
    "consulted_at": "2026-09-14T06:22:12-06:00"
  }
}
JSON,
                'examples' => [
                    'curl' => "curl --request POST \\\n+  --url {$this->documentationUrl('/api/v1/civil-registry/people')} \\\n+  --header 'Accept: application/json' \\\n+  --header 'Authorization: Bearer {token}' \\\n+  --header 'Content-Type: application/json' \\\n+  --data '{\"cedula\":\"113420689\"}'",
                    'javascript' => "const response = await fetch('{$this->documentationUrl('/api/v1/civil-registry/people')}', {\n  method: 'POST',\n  headers: {\n    Accept: 'application/json',\n    Authorization: 'Bearer {token}',\n    'Content-Type': 'application/json',\n  },\n  body: JSON.stringify({ cedula: '113420689' }),\n});\n\nconst data = await response.json();",
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
