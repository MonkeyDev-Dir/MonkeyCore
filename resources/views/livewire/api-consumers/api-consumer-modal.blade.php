<div x-data x-on:open-api-consumer-create.window="$wire.open()" x-on:open-api-consumer-token.window="$wire.openToken($event.detail.consumerId)">
    <x-common.form-modal id="api-consumer-modal" :title="$isTokenOnly ? __('Nuevo API Token') : __('Nueva aplicación API')" :description="$isTokenOnly ? __('Genera otra credencial para esta aplicación.') : __('Crea una credencial independiente para otro sistema.')" size="2xl">
        @if ($plainTextToken)
            <div class="space-y-5">
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-500/30 dark:bg-green-500/10">
                    <p class="font-medium text-green-800 dark:text-green-300">{{ __('Consumidor creado correctamente.') }}</p>
                    <p class="mt-1 text-sm text-green-700 dark:text-green-400">{{ __('Copia este token ahora. No volverá a mostrarse.') }}</p>
                </div>
                <div>
                    <label for="api-consumer-token" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Token') }}</label>
                    <div class="flex gap-2">
                        <input id="api-consumer-token" type="text" readonly value="{{ $plainTextToken }}" class="h-10 min-w-0 flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" />
                        <button type="button" x-on:click="navigator.clipboard.writeText(@js($plainTextToken)); Toast.fire({ icon: 'success', title: @js(__('Token copiado')) })"
                            class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">{{ __('Copiar') }}</button>
                    </div>
                </div>
                <x-common.form-actions>
                    <button type="button" x-on:click="$tsui.close.modal('api-consumer-modal')" wire:click="close" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">{{ __('Cerrar') }}</button>
                </x-common.form-actions>
            </div>
        @else
            <form wire:submit="save" autocomplete="off" class="space-y-5">
                @if ($isTokenOnly)
                    <div>
                        <x-input id="api-consumer-token-name" wire:model="tokenName" :label="__('Nombre del token')" />
                        @error('tokenName')<small class="text-xs text-red-500">{{ $message }}</small>@enderror
                    </div>
                @else
                    <div>
                        <x-input id="api-consumer-name" wire:model="name" :label="__('Nombre de la aplicación')" />
                        @error('name')<small class="text-xs text-red-500">{{ $message }}</small>@enderror
                    </div>
                    <div>
                        <x-textarea id="api-consumer-description" wire:model="description" :label="__('Descripción')" rows="3" />
                        @error('description')<small class="text-xs text-red-500">{{ $message }}</small>@enderror
                    </div>
                @endif
                <div>
                    <x-input id="api-consumer-expires-at" wire:model="expiresAt" type="date" :label="__('Fecha de expiración (opcional)')" />
                    @error('expiresAt')<small class="text-xs text-red-500">{{ $message }}</small>@enderror
                </div>
                <x-common.form-actions>
                    <button type="button" x-on:click="$tsui.close.modal('api-consumer-modal')" wire:click="close" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">{{ __('Cancelar') }}</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50"><span wire:loading.remove wire:target="save">{{ $isTokenOnly ? __('Generar token') : __('Crear y generar token') }}</span><span wire:loading wire:target="save">{{ __('Generando...') }}</span></button>
                </x-common.form-actions>
            </form>
        @endif
    </x-common.form-modal>
</div>
