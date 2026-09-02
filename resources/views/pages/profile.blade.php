@extends('layouts.app')

@section('title', __('Perfil de usuario'))

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ __('Perfil de usuario') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Consulta la información de tu cuenta.') }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex w-full flex-col items-center gap-6 xl:flex-row">
                    <div class="h-20 w-20 overflow-hidden rounded-full border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
                        @if($user->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="{{ __('Avatar de') }} {{ $user->name }}" class="h-full w-full object-cover" />
                        @else
                            <img src="/images/user/owner.png" alt="{{ __('navigation.user_alt') }}" class="h-full w-full object-cover" />
                        @endif
                    </div>
                    <div class="text-center xl:text-left">
                    <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">
                        {{ $user->name }} {{ $user->lastname }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-5 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ __('Avatar') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Genera una nueva apariencia robótica para tu perfil.') }}</p>
                </div>
                <livewire:profile.avatar-selector />
            </div>

            <div class="pt-6">
                <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Información personal') }}</h3>
                <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">{{ __('Nombre') }}</dt>
                        <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">{{ __('Apellidos') }}</dt>
                        <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $user->lastname }}</dd>
                    </div>
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">{{ __('Identificación') }}</dt>
                        <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $user->ide }}</dd>
                    </div>
                    <div>
                        <dt class="text-theme-xs text-gray-500 dark:text-gray-400">{{ __('Correo electrónico') }}</dt>
                        <dd class="mt-1 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $user->email }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
