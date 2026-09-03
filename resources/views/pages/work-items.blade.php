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

        <x-card bordered shadowless>
            <div class="py-8 text-center">
                <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                    <span class="text-2xl font-semibold" aria-hidden="true">✦</span>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('La Mesa de trabajo está lista para comenzar') }}</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">{{ __('Aquí podrás consultar y organizar los casos del equipo.') }}</p>
            </div>
        </x-card>

        <livewire:work-items.work-item-modal />
    </div>
@endsection

