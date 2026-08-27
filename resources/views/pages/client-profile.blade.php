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
                        <i data-lucide="camera" class="h-5 w-5" aria-hidden="true"></i>
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

        <div class="grid items-start grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6 xl:sticky xl:top-20">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Información general') }}</h2>
                <dl class="mt-5 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300" aria-hidden="true">
                            <i data-lucide="contact" class="h-5 w-5" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Identificación fiscal') }}</dt>
                            <dd class="mt-1 truncate font-medium text-gray-800 dark:text-white/90">{{ $client->tax_id ?? __('Sin información') }}</dd>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300" aria-hidden="true">
                            <i data-lucide="mail" class="h-5 w-5" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Correo electrónico') }}</dt>
                            <dd class="mt-1 truncate font-medium text-gray-800 dark:text-white/90">{{ $client->email ?? __('Sin información') }}</dd>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300" aria-hidden="true">
                            <i data-lucide="phone" class="h-5 w-5" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Teléfono') }}</dt>
                            <dd class="mt-1 truncate font-medium text-gray-800 dark:text-white/90">{{ $client->phone ?? __('Sin información') }}</dd>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300" aria-hidden="true">
                            <i data-lucide="link" class="h-5 w-5" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Sitio web') }}</dt>
                            <dd class="mt-1 truncate font-medium text-gray-800 dark:text-white/90">{{ $client->website ?? __('Sin información') }}</dd>
                        </div>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]" aria-label="{{ __('Contenido del perfil del cliente') }}">
                <div x-data="{ activeTab: 'backups' }" class="p-5 lg:p-6">
                    <div class="border-b border-gray-200 dark:border-gray-800">
                        <nav class="flex gap-6 overflow-x-auto" role="tablist" aria-label="{{ __('Secciones del perfil') }}">
                            <button type="button" role="tab" x-bind:aria-selected="activeTab === 'backups'" x-on:click="activeTab = 'backups'" x-bind:class="activeTab === 'backups' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'" class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium transition-colors">
                                {{ __('Respaldos') }}
                            </button>
                            <button type="button" role="tab" x-bind:aria-selected="activeTab === 'projects'" x-on:click="activeTab = 'projects'" x-bind:class="activeTab === 'projects' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'" class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium transition-colors">
                                {{ __('Proyectos') }}
                            </button>
                        </nav>
                    </div>

                    <div x-cloak x-show="activeTab === 'backups'" role="tabpanel" class="pt-6">
                        <div>
                            <livewire:clients.client-backups :client-code="$client->code" :show-history="false" />
                            <div class="mt-4 text-right">
                                <a href="{{ route('clients.backups', ['clientCode' => $client->code]) }}" class="text-sm font-medium text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-300">
                                    {{ __('Ver todos los respaldos registrados') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div x-cloak x-show="activeTab === 'projects'" role="tabpanel" class="pt-6">
                        <livewire:clients.client-projects :client-code="$client->code" />
                    </div>
                </div>
            </section>
        </div>
    </div>

    <livewire:clients.client-logo-modal />
    <livewire:clients.project-modal />
@endsection
