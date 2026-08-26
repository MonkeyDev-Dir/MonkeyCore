@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div>
            <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400">
                {{ __('Volver a usuarios') }}
            </a>
            <h1 class="mt-2 text-xl font-semibold text-gray-800 dark:text-white/90">{{ __('Editar usuario') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Actualiza la información del usuario.') }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Nombre') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90" />
                        @error('name')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="lastname" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Apellidos') }}</label>
                        <input id="lastname" name="lastname" type="text" value="{{ old('lastname', $user->lastname) }}" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90" />
                        @error('lastname')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ide" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Identificación') }}</label>
                        <input id="ide" name="ide" type="text" value="{{ old('ide', $user->ide) }}" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90" />
                        @error('ide')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Correo electrónico') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90" />
                        @error('email')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('users.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">{{ __('Cancelar') }}</a>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">{{ __('Guardar cambios') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
