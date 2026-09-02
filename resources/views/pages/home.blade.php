@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
    <div class="rounded-2xl border border-gray-200 bg-gray-100 p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-950 sm:p-8">
        <p class="text-theme-sm font-medium text-brand-500">MonkeyCore</p>
        <h1 class="mt-2 text-title-md font-semibold text-gray-800 dark:text-white/90">Panel visual inicial</h1>
        <p class="mt-3 max-w-2xl text-theme-sm text-gray-500 dark:text-gray-400">
            El layout base del template ya está integrado. A partir de aquí podemos construir las pantallas reales y reemplazar gradualmente los datos de demostración.
        </p>
    </div>
@endsection
