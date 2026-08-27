<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-900 dark:bg-gray-950">
    <x-table :headers="$headers" :rows="$clients">
        @interact('column_name', $client)
            <a href="{{ route('clients.show', ['clientCode' => $client->code]) }}" class="font-semibold text-gray-900 hover:text-brand-600 hover:underline dark:text-white dark:hover:text-brand-400">
                {{ $client->name }}
            </a>
        @endinteract

        @interact('column_type', $client)
            <span @class([
                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                'border border-blue-300 bg-blue-100 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/15 dark:text-blue-300' => $client->type === 'company',
                'border border-violet-200 bg-violet-50 text-violet-600 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-400' => $client->type !== 'company',
            ])>{{ __($client->type === 'company' ? 'Plan empresarial' : 'Plan personal') }}</span>
        @endinteract

        @interact('column_contact', $client)
            {{ $client->contacts->first()?->name ?? __('Sin contacto') }}
        @endinteract

        @interact('column_email', $client)
            {{ $client->email ?? __('Sin correo') }}
        @endinteract

        <x-slot:empty>
            {{ __('No hay clientes registrados.') }}
        </x-slot:empty>
    </x-table>
</div>
