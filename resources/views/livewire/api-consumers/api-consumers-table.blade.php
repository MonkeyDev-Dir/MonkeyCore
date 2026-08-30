<div class="space-y-4" x-data="{
    async confirmDelete(consumerId) {
        const isDark = document.documentElement.classList.contains('dark');
        const result = await Swal.fire({
            title: @js(__('¿Eliminar aplicación?')),
            text: @js(__('Esta acción eliminará la aplicación y todos sus tokens.')),
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            confirmButtonText: @js(__('Eliminar')),
            cancelButtonText: @js(__('Cancelar')),
            background: isDark ? '#111827' : '#ffffff',
            color: isDark ? '#f3f4f6' : '#1f2937',
            iconColor: isDark ? '#f87171' : '#dc2626',
            customClass: {
                popup: 'app-confirmation',
                title: 'app-confirmation-title',
                htmlContainer: 'app-confirmation-text',
                icon: 'app-confirmation-icon',
                actions: 'app-confirmation-actions',
                confirmButton: 'app-confirmation-confirm',
                cancelButton: 'app-confirmation-cancel',
            },
        });

        if (result.isConfirmed) {
            $wire.deleteConsumer(consumerId);
        }
    }
}">
    <div class="flex justify-end">
        <div class="relative w-full sm:max-w-sm">
            <label for="api-consumers-search" class="sr-only">{{ __('Buscar aplicaciones API') }}</label>
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
            <input id="api-consumers-search" type="search" wire:model.live.debounce.500ms="search" placeholder="{{ __('Buscar aplicaciones...') }}"
                class="h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-9 pr-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-gray-500" />
        </div>
    </div>

    <x-card bordered shadowless paddingless>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Aplicación') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Tokens activos') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Último uso') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($consumers as $consumer)
                        <tr wire:key="api-consumer-{{ $consumer->id }}" class="align-middle">
                            <td class="px-5 py-4 align-middle">
                                <p class="font-medium text-gray-800 dark:text-white/90">{{ $consumer->name }}</p>
                                @if ($consumer->description)
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $consumer->description }}</p>
                                @endif
                                <x-badge :text="__($consumer->active ? 'Activo' : 'Inactivo')" :color="$consumer->active ? 'green' : 'gray'" light round />
                            </td>
                            <td class="px-5 py-4 align-middle">
                                <div class="space-y-2">
                                    @forelse ($consumer->tokens as $token)
                                        <div wire:key="api-token-{{ $token->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-800">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-200">{{ $token->name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ in_array('*', $token->abilities ?? [], true) ? __('Acceso completo a las APIs') : implode(', ', $token->abilities ?? []) }}</p>
                                            </div>
                                            <button type="button" wire:click="revokeToken({{ $consumer->id }}, {{ $token->id }})" wire:loading.attr="disabled"
                                                title="{{ __('Revocar token') }}" aria-label="{{ __('Revocar token') }}"
                                                class="inline-flex size-8 shrink-0 items-center justify-center rounded-md border border-red-200 text-red-600 transition hover:bg-red-50 disabled:opacity-50 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10">
                                                <x-icon name="arrow-uturn-left" class="size-4" />
                                            </button>
                                        </div>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Sin tokens activos') }}</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 align-middle text-sm text-gray-600 dark:text-gray-300">
                                {{ $consumer->tokens->first()?->last_used_at?->timezone(config('services.bccr.timezone', 'America/Costa_Rica'))->format('d/m/Y H:i') ?? __('Nunca') }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right align-middle">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @if ($consumer->active)
                                        <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-api-consumer-token', { detail: { consumerId: {{ $consumer->id }} } }))"
                                            title="{{ __('Nuevo token') }}" aria-label="{{ __('Nuevo token') }}"
                                            class="inline-flex size-9 items-center justify-center rounded-lg border border-brand-200 text-brand-700 transition hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-300 dark:hover:bg-brand-500/10">
                                            <x-icon name="arrow-path" class="size-4" />
                                        </button>
                                        <button type="button" wire:click="deactivate({{ $consumer->id }})" wire:loading.attr="disabled"
                                            title="{{ __('Desactivar') }}" aria-label="{{ __('Desactivar') }}"
                                            class="inline-flex size-9 items-center justify-center rounded-lg border border-orange-200 text-orange-700 transition hover:bg-orange-50 dark:border-orange-500/30 dark:text-orange-300 dark:hover:bg-orange-500/10">
                                            <x-icon name="x-circle" class="size-4" />
                                        </button>
                                    @endif
                                    <button type="button" x-on:click="confirmDelete({{ $consumer->id }})" wire:loading.attr="disabled"
                                        title="{{ __('Eliminar aplicación') }}" aria-label="{{ __('Eliminar aplicación') }}"
                                        class="inline-flex size-9 items-center justify-center rounded-lg border border-red-200 text-red-700 transition hover:bg-red-50 disabled:opacity-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                                        <x-icon name="trash" class="size-4" />
                                        <span class="sr-only">{{ __('Eliminar aplicación') }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No hay aplicaciones API registradas.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
