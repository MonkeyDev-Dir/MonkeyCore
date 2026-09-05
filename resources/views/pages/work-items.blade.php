@extends('layouts.app')

@section('title', __('Mesa de trabajo'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">{{ __('Mesa de trabajo') }}</h1>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Organiza soporte, integraciones, planificación y desarrollo en un solo lugar.') }}</p>
            </div>
            <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-work-item-create'))" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600">
                <span class="text-lg leading-none">+</span>
                {{ __('Nuevo caso') }}
            </button>
        </div>

        <livewire:work-items.work-items-table />

        <livewire:work-items.work-item-modal />
    </div>
@endsection

