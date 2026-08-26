<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-white/[0.03]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Cliente') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Tipo') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Contacto principal') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Correo electrónico') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($clients as $client)
                    <tr wire:key="client-{{ $client->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                            <a href="{{ route('clients.show', ['clientCode' => $client->code]) }}" class="font-semibold text-gray-900 hover:text-brand-600 hover:underline dark:text-white dark:hover:text-brand-400">
                                {{ $client->name }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400"><span @class([
                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                            'border border-blue-300 bg-blue-100 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/15 dark:text-blue-300' => $client->type === 'company',
                            'border border-violet-200 bg-violet-50 text-violet-600 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-400' => $client->type !== 'company',
                        ])>{{ __($client->type === 'company' ? 'Plan empresarial' : 'Plan personal') }}</span></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $client->contacts->first()?->name ?? __('Sin contacto') }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $client->email ?? __('Sin correo') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No hay clientes registrados.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
