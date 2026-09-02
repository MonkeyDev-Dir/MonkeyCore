@extends('layouts.app')

@section('title', __('Usuarios'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">{{ __('Usuarios') }}</h1>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Administra los usuarios del sistema.') }}</p>
            </div>
            <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-user-create'))"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600">
                <span class="text-lg leading-none">+</span>
                {{ __('Nuevo usuario') }}
            </button>
        </div>

        <livewire:users.users-table />

        <livewire:users.user-modal />
    </div>
@endsection
