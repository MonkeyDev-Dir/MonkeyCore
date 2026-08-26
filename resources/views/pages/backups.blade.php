@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">{{ __('Respaldos') }}</h1>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ __('Respaldos privados de PostgreSQL almacenados en Amazon S3.') }}</p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-4 font-medium">{{ __('Archivo') }}</th>
                            <th class="px-6 py-4 font-medium">{{ __('Fecha') }}</th>
                            <th class="px-6 py-4 font-medium">{{ __('Tamaño') }}</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($backups as $backup)
                            <tr class="text-gray-700 dark:text-gray-300">
                                <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $backup['name'] }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ $backup['last_modified']->format('d/m/Y H:i:s') }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('backups.download', ['path' => $backup['path']]) }}" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ __('Descargar') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-gray-500">{{ __('Todavía no hay respaldos disponibles.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
