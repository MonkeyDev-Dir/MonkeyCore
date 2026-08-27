<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-900 dark:bg-gray-950">
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
