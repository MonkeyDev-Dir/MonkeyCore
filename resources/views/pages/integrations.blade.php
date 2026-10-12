@extends('layouts.app')

@section('title', __('Integraciones'))

@section('content')
    <div
        x-data="{
            testUrl: @js(route('integrations.apifycr.test')),
            loading: false,
            result: null,
            cooldown: 0,
            cooldownTimer: null,
            async runApifyTest() {
                if (this.loading || this.cooldown > 0) {
                    return;
                }

                this.loading = true;
                this.result = null;

                try {
                    const response = await fetch(this.testUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                    });
                    const payload = await response.json();
                    const retryAfterHeader = Number(response.headers.get('Retry-After') ?? 0);

                    payload.retry_after = Number(payload.retry_after ?? retryAfterHeader) || null;
                    this.result = payload;
                    this.startCooldown(payload.retry_after);
                } catch (error) {
                    this.result = {
                        ok: false,
                        status: null,
                        response: null,
                        error: error.message,
                        retry_after: null,
                    };
                } finally {
                    this.loading = false;
                }
            },
            startCooldown(seconds) {
                if (!seconds) {
                    return;
                }

                clearInterval(this.cooldownTimer);
                this.cooldown = seconds;
                this.cooldownTimer = setInterval(() => {
                    this.cooldown -= 1;

                    if (this.cooldown <= 0) {
                        clearInterval(this.cooldownTimer);
                    }
                }, 1000);
            },
        }"
        class="space-y-6"
    >
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
                                            <x-icon :name="$integration['icon'] ?? 'banknotes'" lg />
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
                                    @if ($integration['sync_route'] ?? null)
                                        <form method="POST" action="{{ route($integration['sync_route']) }}">
                                            @csrf
                                            <x-button submit icon="arrow-path" sm tooltip="{{ __('Ejecutar ahora') }}" aria-label="{{ __('Ejecutar ahora') }}" />
                                        </form>
                                    @elseif ($integration['test_route'] ?? null)
                                        <button
                                            type="button"
                                            x-on:click="$tsui.open.modal('apifycr-test-modal'); runApifyTest()"
                                            aria-label="{{ __('Probar servicio') }}"
                                            title="{{ __('Probar servicio') }}"
                                            class="inline-flex items-center rounded-lg border border-brand-200 px-3 py-2 text-brand-600 hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-400 dark:hover:bg-brand-500/10"
                                        >
                                            <x-icon name="magnifying-glass" sm />
                                        </button>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">{{ __('Consulta automática') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

    <x-modal id="apifycr-test-modal" size="4xl" center>
        <x-common.modal-close x-on:click="$tsui.close.modal('apifycr-test-modal')" />
        <x-common.modal-header :title="__('Prueba de ApifyCR')" :description="__('Consulta una cédula aleatoria y muestra la respuesta completa del proveedor.')" />

        <div class="max-h-[calc(100vh-14rem)] space-y-5 overflow-y-auto px-4 py-5 custom-scrollbar sm:px-5">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-900/50">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Cédula consultada') }}</p>
                    <p class="font-mono font-medium text-gray-800 dark:text-white/90" x-text="result?.cedula ?? '—'"></p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Estado HTTP') }}</p>
                    <p class="font-medium text-gray-800 dark:text-white/90" x-text="result?.status ?? '—'"></p>
                </div>
                <button type="button" x-on:click="runApifyTest()" x-bind:disabled="loading || cooldown > 0" class="rounded-lg border border-brand-200 px-3 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-brand-500/30 dark:text-brand-400 dark:hover:bg-brand-500/10">
                    <span x-show="!loading && cooldown <= 0">{{ __('Probar de nuevo') }}</span>
                    <span x-show="loading">{{ __('Consultando...') }}</span>
                    <span x-show="!loading && cooldown > 0">{{ __('Disponible nuevamente en') }} <span x-text="cooldown"></span>s</span>
                </button>
            </div>

            <div x-show="loading" class="flex items-center justify-center gap-3 py-12 text-sm text-gray-500 dark:text-gray-400">
                <span class="size-5 animate-spin rounded-full border-2 border-brand-500 border-t-transparent" aria-hidden="true"></span>
                {{ __('Consultando ApifyCR...') }}
            </div>

            <div x-show="!loading && result" x-cloak class="space-y-3">
                <div x-show="result?.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300" x-text="result?.error"></div>
                <div x-show="result?.retry_after" class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-300">
                    {{ __('El proveedor solicitó esperar') }} <span class="font-semibold" x-text="result?.retry_after"></span> {{ __('segundos antes de reintentar.') }}
                </div>
                <pre class="overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs leading-5 text-gray-100" x-text="JSON.stringify(result?.response ?? result, null, 2)"></pre>
            </div>
        </div>
    </x-modal>
    </div>
@endsection
