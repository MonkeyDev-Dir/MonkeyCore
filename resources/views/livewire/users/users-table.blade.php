<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-900 dark:bg-gray-950">
    <div class="flex justify-end border-b border-gray-200 px-4 py-4 dark:border-gray-900 sm:px-6">
        <div class="relative w-full sm:max-w-sm">
            <label for="users-search" class="sr-only">{{ __('Buscar usuarios') }}</label>
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
            <input id="users-search" type="search" wire:model.live.debounce.500ms="search" placeholder="{{ __('Search users by name, identification or email...') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-9 pr-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-gray-500" />
        </div>
    </div>
    <x-table :headers="$headers" :rows="$users">
        @interact('column_name', $user)
            <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-user-edit', { detail: { userId: {{ $user->id }} } }))"
                class="text-left font-medium text-brand-500 hover:underline">
                {{ $user->name }} {{ $user->lastname }}
            </button>
        @endinteract

        <x-slot:empty>
            {{ __('No hay usuarios registrados.') }}
        </x-slot:empty>
    </x-table>
</div>
