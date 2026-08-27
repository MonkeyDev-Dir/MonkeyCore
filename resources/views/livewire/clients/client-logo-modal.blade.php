<div x-data="{ modalReady: false }"
    x-on:open-client-logo-modal.window="modalReady = false; $wire.open($event.detail.clientCode).then(() => modalReady = true)"
    x-on:client-logo-updated.window="modalReady = false; Toast.fire({ icon: 'success', title: $event.detail.message })">
    <x-common.form-modal id="client-logo-modal" :title="__('Actualizar logo')" :description="__('Selecciona una nueva imagen para este cliente.')" size="2xl">
        <div x-cloak x-show="!modalReady" class="flex min-h-[300px] items-center justify-center" role="status" aria-live="polite">
            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                <svg class="h-5 w-5 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V1C6.925 1 2 5.925 2 12h2Zm8 8a8 8 0 0 1-8-8H1c0 6.075 4.925 11 11 11v-3Z"></path>
                </svg>
                <span>{{ __('Cargando información...') }}</span>
            </div>
        </div>

        <div x-cloak x-show="modalReady">
            <form wire:submit="save" autocomplete="off" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Logo actual') }}</p>
                        <div class="flex h-36 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03]">
                            @if($currentImageUrl)
                                <img src="{{ $currentImageUrl }}" alt="{{ __('Logo actual del cliente') }}" class="h-full w-full object-contain" />
                            @else
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Sin logo') }}</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Vista previa') }}</p>
                        <div class="flex h-36 items-center justify-center overflow-hidden rounded-xl border border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03]">
                            @if($image)
                                <img src="{{ $image->temporaryUrl() }}" alt="{{ __('Vista previa del nuevo logo') }}" class="h-full w-full object-contain" />
                            @else
                                <span class="text-center text-xs text-gray-500 dark:text-gray-400">{{ __('Selecciona una imagen para verla aquí.') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <label for="client-logo-image" class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-5 py-7 text-center transition-colors hover:border-brand-400 hover:bg-brand-50/50 dark:border-gray-700 dark:bg-white/[0.03] dark:hover:border-brand-500 dark:hover:bg-brand-500/5">
                        <svg class="h-8 w-8 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path d="M4 7.5A1.5 1.5 0 0 1 5.5 6h2l1.25-1.5h6.5L16.5 6h2A1.5 1.5 0 0 1 20 7.5v9A1.5 1.5 0 0 1 18.5 18h-13A1.5 1.5 0 0 1 4 16.5v-9Z" stroke-linejoin="round" />
                            <circle cx="12" cy="12" r="3.25" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Haz clic para seleccionar una imagen') }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('JPG, PNG o WEBP · máximo 2 MB') }}</span>
                        <input id="client-logo-image" wire:model="image" type="file" accept="image/jpeg,image/png,image/webp" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                    </label>
                    @error('image')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
                </div>

                <x-common.form-actions>
                    <button type="button" x-on:click="$tsui.close.modal('client-logo-modal')" wire:click="close" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">{{ __('Cancelar') }}</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="save">{{ __('Actualizar logo') }}</span>
                        <span wire:loading wire:target="save">{{ __('Guardando...') }}</span>
                    </button>
                </x-common.form-actions>
            </form>
        </div>
    </x-common.form-modal>
</div>
