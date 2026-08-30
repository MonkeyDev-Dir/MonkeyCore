@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">{{ __('Integraciones') }}</h1>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Consulta el estado de las integraciones del sistema.') }}</p>
        </div>

        <x-card bordered shadowless paddingless>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Integración') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Estado') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Última ejecución') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Acción') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($integrations as $integration)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                            <x-icon name="banknotes" lg />
                                        </span>
                                        <p class="font-medium text-gray-800 dark:text-white/90">{{ $integration['name'] }}</p>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $integration['description'] }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <x-badge
                                        :text="__($integration['status']['active'] ? 'Activa' : 'Inactiva')"
                                        :color="$integration['status']['active'] ? 'green' : 'gray'"
                                        light
                                        round
                                    />
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $integration['status']['last_run_at']?->timezone('America/Costa_Rica')->format('d/m/Y H:i') ?? __('Sin ejecuciones registradas') }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <form method="POST" action="{{ route('integrations.sync') }}">
                                        @csrf
                                        <x-button submit icon="arrow-path" sm tooltip="{{ __('Ejecutar ahora') }}" aria-label="{{ __('Ejecutar ahora') }}" />
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
@endsection
