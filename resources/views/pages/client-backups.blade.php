@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <a href="{{ route('clients.show', ['clientCode' => $client->code]) }}" class="text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400">
                {{ __('Volver al perfil del cliente') }}
            </a>
        </div>

        <div>
            <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">{{ __('Respaldos de :name', ['name' => $client->name]) }}</h1>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Consulta los respaldos recientes e históricos registrados para este cliente.') }}</p>
        </div>

        <div class="space-y-8 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <livewire:clients.client-backups :client-code="$client->code" />
        </div>
    </div>
@endsection
