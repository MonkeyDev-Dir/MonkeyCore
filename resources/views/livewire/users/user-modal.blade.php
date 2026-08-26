<div x-data="{ modalOpen: false, modalReady: false, passwordVisible: false, confirmationVisible: false }"
    x-on:open-user-create.window="modalOpen = true; modalReady = false; $wire.openCreate().then(() => modalReady = true)"
    x-on:open-user-edit.window="modalOpen = true; modalReady = false; $wire.edit($event.detail.userId).then(() => modalReady = true)"
    x-on:user-saved.window="modalOpen = false; Toast.fire({ icon: 'success', title: $event.detail.message })">
    <div x-cloak x-show="modalOpen" x-transition.opacity
        class="fixed inset-0 z-[100000] flex items-start justify-center overflow-y-auto p-3 sm:items-center sm:p-5">
        <div x-transition.opacity class="fixed inset-0 bg-gray-950/70" x-on:click="modalOpen = false" wire:click="close"></div>

        <div x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="scale-100 opacity-100"
            x-transition:leave-end="scale-95 opacity-0"
            class="relative z-10 my-auto max-h-[calc(100vh-1.5rem)] w-full max-w-[700px] overflow-x-hidden overflow-y-auto rounded-2xl border border-gray-200 bg-white p-4 shadow-[0_4px_10px_rgb(0_0_0_/_0.12)] dark:border-gray-800 dark:bg-gray-900 dark:shadow-[0_4px_10px_rgb(0_0_0_/_0.30)] sm:rounded-3xl sm:p-5 lg:p-8"
            x-bind:aria-busy="!modalReady">
            <x-common.modal-close x-on:click="modalOpen = false" wire:click="close" />

            <div x-cloak x-show="!modalReady" class="flex min-h-[520px] items-center justify-center" role="status" aria-live="polite">
                <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="h-5 w-5 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V1C6.925 1 2 5.925 2 12h2Zm8 8a8 8 0 0 1-8-8H1c0 6.075 4.925 11 11 11v-3Z"></path>
                    </svg>
                    <span>{{ __('Cargando información...') }}</span>
                </div>
            </div>

            <div x-cloak x-show="modalReady">
            <div class="mb-6 pr-8">
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                    {{ $userId === null ? __('Nuevo usuario') : __('Editar usuario') }}
                </h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Completa la información del usuario.') }}
                </p>
            </div>

            <form wire:submit="save" class="space-y-5">
                <fieldset wire:loading.attr="disabled" wire:target="lookupPerson" class="contents">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-12">
                    <div class="col-span-12 min-w-0 sm:col-span-6">
                        <label for="modal-ide" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Identificación') }}</label>
                        <div class="flex items-center gap-2">
                            <input id="modal-ide" wire:model="ide" type="text" wire:loading.attr="disabled" wire:target="lookupPerson" class="h-10 min-w-0 flex-1 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90 disabled:cursor-not-allowed disabled:bg-gray-100 dark:disabled:bg-white/[0.03]" />
                            @if($userId === null)
                                <button type="button" wire:click="lookupPerson" wire:loading.attr="disabled" wire:target="lookupPerson" aria-label="{{ __('Consultar identificación') }}" title="{{ __('Consultar identificación') }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-brand-500 px-3 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
                                    <span wire:loading.remove wire:target="lookupPerson">{{ __('Consultar') }}</span>
                                    <span wire:loading wire:target="lookupPerson" aria-label="{{ __('Consultando...') }}">
                                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V1C6.925 1 2 5.925 2 12h2Zm8 8a8 8 0 0 1-8-8H1c0 6.075 4.925 11 11 11v-3Z"></path>
                                        </svg>
                                    </span>
                                </button>
                            @endif
                        </div>
                        @error('ide')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
                        @error('lookup')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
                        <div wire:loading.flex wire:target="lookupPerson" class="mt-2 items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <svg class="h-4 w-4 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V1C6.925 1 2 5.925 2 12h2Zm8 8a8 8 0 0 1-8-8H1c0 6.075 4.925 11 11 11v-3Z"></path>
                            </svg>
                            <span>{{ __('Consultando información...') }}</span>
                        </div>
                    </div>
                    <div class="col-span-12 min-w-0 sm:col-span-6 sm:col-start-1">
                        <label for="modal-name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Nombre') }}</label>
                        <input id="modal-name" wire:model="name" type="text" wire:loading.attr="disabled" wire:target="lookupPerson" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90 disabled:cursor-not-allowed disabled:bg-gray-100 dark:disabled:bg-white/[0.03]" />
                        @error('name')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-span-12 min-w-0 sm:col-span-6 sm:col-start-7">
                        <label for="modal-lastname" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Apellidos') }}</label>
                        <input id="modal-lastname" wire:model="lastname" type="text" wire:loading.attr="disabled" wire:target="lookupPerson" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90 disabled:cursor-not-allowed disabled:bg-gray-100 dark:disabled:bg-white/[0.03]" />
                        @error('lastname')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-span-12 min-w-0">
                        <label for="modal-email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Correo electrónico') }}</label>
                        <input id="modal-email" wire:model="email" type="email" wire:loading.attr="disabled" wire:target="lookupPerson" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90 disabled:cursor-not-allowed disabled:bg-gray-100 dark:disabled:bg-white/[0.03]" />
                        @error('email')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-span-12 min-w-0 sm:col-span-6">
                        <label for="modal-password" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Contraseña') }}</label>
                        <div class="relative">
                            <input id="modal-password" wire:model="password" :type="passwordVisible ? 'text' : 'password'" wire:loading.attr="disabled" wire:target="lookupPerson" {{ $userId === null ? 'required' : '' }} class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 pr-11 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90 disabled:cursor-not-allowed disabled:bg-gray-100 dark:disabled:bg-white/[0.03]" />
                            <button type="button" x-on:click="passwordVisible = !passwordVisible" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" :aria-label="passwordVisible ? '{{ __('Ocultar contraseña') }}' : '{{ __('Mostrar contraseña') }}'">
                                <svg x-show="!passwordVisible" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                <svg x-show="passwordVisible" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m3 3 18 18M10.58 10.58a2 2 0 0 0 2.83 2.83M9.88 5.1A10.6 10.6 0 0 1 12 4.9c6.25 0 9.75 7.1 9.75 7.1a17.5 17.5 0 0 1-3.12 3.93M6.23 6.23C3.66 8.04 2.25 12 2.25 12s3.5 7.1 9.75 7.1a10.5 10.5 0 0 0 3.26-.52"/></svg>
                            </button>
                        </div>
                        @error('password')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-span-12 min-w-0 sm:col-span-6">
                        <label for="modal-password-confirmation" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Confirmar contraseña') }}</label>
                        <div class="relative">
                            <input id="modal-password-confirmation" wire:model="password_confirmation" :type="confirmationVisible ? 'text' : 'password'" wire:loading.attr="disabled" wire:target="lookupPerson" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 pr-11 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90 disabled:cursor-not-allowed disabled:bg-gray-100 dark:disabled:bg-white/[0.03]" />
                            <button type="button" x-on:click="confirmationVisible = !confirmationVisible" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" :aria-label="confirmationVisible ? '{{ __('Ocultar contraseña') }}' : '{{ __('Mostrar contraseña') }}'">
                                <svg x-show="!confirmationVisible" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                <svg x-show="confirmationVisible" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m3 3 18 18M10.58 10.58a2 2 0 0 0 2.83 2.83M9.88 5.1A10.6 10.6 0 0 1 12 4.9c6.25 0 9.75 7.1 9.75 7.1a17.5 17.5 0 0 1-3.12 3.93M6.23 6.23C3.66 8.04 2.25 12 2.25 12s3.5 7.1 9.75 7.1a10.5 10.5 0 0 0 3.26-.52"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-3">
                    <button type="button" x-on:click="modalOpen = false" wire:click="close" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">{{ __('Cancelar') }}</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save, lookupPerson" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="save">{{ __('Guardar cambios') }}</span>
                        <span wire:loading wire:target="save">{{ __('Guardando...') }}</span>
                    </button>
                </div>
                </fieldset>
            </form>
            </div>
        </div>
    </div>
</div>
