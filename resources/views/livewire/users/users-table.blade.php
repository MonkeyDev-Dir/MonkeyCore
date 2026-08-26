<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-white/[0.03]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Nombre') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Identificación') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Correo electrónico') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-800 dark:text-white/90">
                            <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-user-edit', { detail: { userId: {{ $user->id }} } }))"
                                class="text-left text-brand-500 hover:underline">
                                {{ $user->name }} {{ $user->lastname }}
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $user->ide }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No hay usuarios registrados.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
