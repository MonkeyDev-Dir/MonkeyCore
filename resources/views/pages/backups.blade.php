@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">{{ __('Respaldos') }}</h1>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Respaldos privados de PostgreSQL almacenados en Amazon S3.') }}</p>
        </div>

        <livewire:backups.backups-table />
    </div>
@endsection
