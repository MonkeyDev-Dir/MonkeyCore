@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span>{{ __('APIS') }}</span>
                    <span aria-hidden="true">/</span>
                    <span>{{ $documentation['version'] }}</span>
                </div>
                <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">{{ $documentation['title'] }}</h1>
                <p class="mt-1 max-w-3xl text-theme-sm text-gray-500 dark:text-gray-400">{{ $documentation['description'] }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-badge :text="$documentation['version']" color="blue" light round />
                <a href="{{ route('scramble.docs.document') }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    <x-icon name="arrow-down-tray" class="size-4" />
                    {{ __('OpenAPI JSON') }}
                </a>
            </div>
        </div>

        <x-card bordered shadowless>
            <div class="space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Información general') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Usa la URL base para construir las solicitudes de esta API.') }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3 font-mono text-sm text-gray-700 dark:bg-gray-800/60 dark:text-gray-200">
                    {{ $documentation['baseUrl'] }}
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                        <h3 class="font-medium text-gray-800 dark:text-white/90">{{ __('Autenticación') }}</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $documentation['authentication'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                        <h3 class="font-medium text-gray-800 dark:text-white/90">{{ __('Encabezados requeridos') }}</h3>
                        <ul class="mt-2 space-y-1 font-mono text-xs text-gray-600 dark:text-gray-300">
                            @foreach ($documentation['headers'] as $header)
                                <li>{{ $header }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                        <h3 class="font-medium text-gray-800 dark:text-white/90">{{ __('Límite de consumo') }}</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $documentation['rateLimit'] }}</p>
                    </div>
                </div>
            </div>
        </x-card>

        <div class="space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Endpoints') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Todos los endpoints requieren autenticación mediante un token activo.') }}</p>
            </div>

            @foreach ($documentation['endpoints'] as $endpoint)
                <x-card bordered shadowless>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <span class="inline-flex w-fit items-center rounded-md bg-green-50 px-2.5 py-1 font-mono text-xs font-semibold text-green-700 dark:bg-green-500/10 dark:text-green-300">
                                {{ $endpoint['method'] }}
                            </span>
                            <code class="break-all text-sm text-gray-700 dark:text-gray-200">{{ $documentation['baseUrl'] }}{{ $endpoint['path'] }}</code>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $endpoint['description'] }}</p>

                        @if (count($endpoint['parameters']) > 0)
                            <div>
                                <h3 class="mb-2 text-sm font-medium text-gray-800 dark:text-white/90">{{ __('Parámetros de ruta') }}</h3>
                                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                                            <tr>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Nombre') }}</th>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Tipo') }}</th>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Requerido') }}</th>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Descripción') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                            @foreach ($endpoint['parameters'] as $parameter)
                                                <tr>
                                                    <td class="px-4 py-2 font-mono text-xs text-gray-700 dark:text-gray-200">{{ $parameter['name'] }}</td>
                                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $parameter['type'] }}</td>
                                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $parameter['required'] ? __('Sí') : __('No') }}</td>
                                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $parameter['description'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>

        <x-card bordered shadowless>
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Respuestas HTTP') }}</h2>
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Código') }}</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Descripción') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach ($documentation['responses'] as $response)
                                <tr>
                                    <td class="px-4 py-2 font-mono text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $response['code'] }}</td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $response['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </x-card>

        <x-card bordered shadowless>
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Ejemplo de respuesta') }}</h2>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs leading-6 text-gray-100"><code>{{ $documentation['response'] }}</code></pre>
            </div>
        </x-card>

        <x-card bordered shadowless>
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Ejemplos de consumo') }}</h2>
                @foreach ($documentation['examples'] as $language => $example)
                    <div>
                        <h3 class="mb-2 text-sm font-medium capitalize text-gray-700 dark:text-gray-200">{{ $language }}</h3>
                        <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs leading-6 text-gray-100"><code>{{ $example }}</code></pre>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
@endsection
