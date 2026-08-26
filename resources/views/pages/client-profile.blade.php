@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <a href="{{ route('clients.index') }}" class="text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400">
                {{ __('Volver a clientes') }}
            </a>
        </div>

        <div x-data="{ imageUrl: @js($imageUrl) }" x-on:client-logo-updated.window="imageUrl = $event.detail.url" class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-client-logo-modal', { detail: { clientCode: '{{ $client->code }}' } }))" class="group relative h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:border-gray-700 dark:focus:ring-offset-gray-900" aria-label="{{ __('Actualizar logo') }}">
                    <img x-show="imageUrl" x-bind:src="imageUrl" alt="{{ __('Logo de :name', ['name' => $client->name]) }}" class="h-full w-full object-cover" />
                    <span x-show="!imageUrl" class="flex h-full w-full items-center justify-center bg-brand-50 text-2xl font-semibold text-brand-500 dark:bg-brand-500/10">
                        {{ str($client->name)->substr(0, 1)->upper() }}
                    </span>
                    <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-gray-950/60 text-white opacity-0 transition-opacity duration-200 ease-in-out group-hover:opacity-100 group-focus-visible:opacity-100" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                            <path d="M4 7.5A1.5 1.5 0 0 1 5.5 6h2l1.25-1.5h6.5L16.5 6h2A1.5 1.5 0 0 1 20 7.5v9A1.5 1.5 0 0 1 18.5 18h-13A1.5 1.5 0 0 1 4 16.5v-9Z" stroke-linejoin="round" />
                            <circle cx="12" cy="12" r="3.25" />
                        </svg>
                    </span>
                </button>

                <div>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $client->name }}</h1>
                    <span @class([
                        'mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                        'border border-blue-300 bg-blue-100 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/15 dark:text-blue-300' => $client->type === 'company',
                        'border border-violet-200 bg-violet-50 text-violet-600 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-400' => $client->type !== 'company',
                    ])>{{ __($client->type === 'company' ? 'Plan empresarial' : 'Plan personal') }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Información general') }}</h2>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Identificación fiscal') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $client->tax_id ?? __('Sin información') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Correo electrónico') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $client->email ?? __('Sin información') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Teléfono') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $client->phone ?? __('Sin información') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Sitio web') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $client->website ?? __('Sin información') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Contacto principal') }}</h2>
                @if($primaryContact)
                    <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Nombre') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $primaryContact->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Cargo') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $primaryContact->position ?? __('Sin información') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Correo electrónico') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $primaryContact->email ?? __('Sin información') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Teléfono') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $primaryContact->phone ?? __('Sin información') }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-5 text-sm text-gray-500 dark:text-gray-400">{{ __('Sin contacto registrado.') }}</p>
                @endif
            </section>
        </div>
    </div>

    <livewire:clients.client-logo-modal />
@endsection
