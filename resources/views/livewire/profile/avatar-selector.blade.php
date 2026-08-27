<div>
    <button type="button" wire:click="open" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        {{ __('Cambiar avatar') }}
    </button>

    <x-modal id="avatar-selector" wire="isOpen" z-index="z-[70]" size="3xl" scrollable center x-on:close="$wire.close()">
        <x-common.modal-close x-on:click="$tsui.close.modal('avatar-selector')" wire:click="close" />
        <x-common.modal-header :title="__('Elige tu avatar')" :description="__('Selecciona una apariencia robótica para tu perfil.')" />

        <div wire:loading wire:target="open">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                @foreach(range(1, 20) as $placeholder)
                    <div wire:key="avatar-placeholder-{{ $placeholder }}" class="flex aspect-square items-center justify-center rounded-lg border-2 border-gray-200 bg-gray-50 p-1.5 dark:border-gray-800 dark:bg-gray-950">
                        <svg class="h-20 w-20 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none" aria-label="{{ __('Generando opciones...') }}">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V1C6.925 1 2 5.925 2 12h2Z"></path>
                        </svg>
                    </div>
                @endforeach
            </div>
        </div>

        <div wire:loading.remove wire:target="open" class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach($options as $avatarPath)
                <button type="button" wire:key="avatar-option-{{ $avatarPath }}" wire:click="select('{{ $avatarPath }}')" class="relative aspect-square rounded-lg border-2 bg-gray-50 p-1.5 transition hover:border-brand-400 dark:bg-gray-950 {{ $selectedAvatarPath === $avatarPath ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-gray-200 dark:border-gray-800' }}">
                    <img src="{{ asset('storage/'.$avatarPath) }}" alt="{{ __('Opción de avatar') }}" class="mx-auto aspect-square w-full max-w-20 object-contain" />
                    @if($selectedAvatarPath === $avatarPath)
                        <span class="absolute right-2 top-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-white" aria-label="{{ __('Seleccionado') }}">✓</span>
                    @endif
                </button>
            @endforeach
        </div>

        @error('selectedAvatarPath')
            <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
        @enderror

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" x-on:click="$tsui.close.modal('avatar-selector')" wire:click="close" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                {{ __('Cancelar') }}
            </button>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="save">{{ __('Usar este avatar') }}</span>
                <span wire:loading wire:target="save">{{ __('Guardando...') }}</span>
            </button>
        </div>
    </x-modal>
</div>
