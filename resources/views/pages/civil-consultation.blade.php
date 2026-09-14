@extends('layouts.app')

@section('title', __('Consulta civil'))

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">{{ __('Consulta civil') }}</h1>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Consulta información de personas y entidades jurídicas por su identificación.') }}</p>
        </div>

        <livewire:civil-consultation.civil-consultation />
    </div>
@endsection
