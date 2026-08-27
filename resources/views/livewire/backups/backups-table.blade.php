<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-900 dark:bg-gray-950">
    <div class="flex justify-end border-b border-gray-200 px-4 py-4 dark:border-gray-900 sm:px-6">
        <div class="relative w-full sm:max-w-sm">
            <label for="backups-search" class="sr-only">{{ __('Buscar respaldos') }}</label>
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
            <input id="backups-search" type="search" wire:model.live.debounce.500ms="search" placeholder="{{ __('Busca por archivo, cliente o proyecto...') }}" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-9 pr-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-gray-500" />
        </div>
    </div>

    <div class="[&>div>div:last-child]:px-4 [&>div>div:last-child]:pb-4 [&>div>div:last-child]:sm:px-6">
        <x-table :headers="$headers" :rows="$backups" paginate :on-each-side="0">
        @interact('column_name', $backup)
            <div>
                <span class="flex items-center gap-2 font-medium"><i data-lucide="database" class="h-4 w-4 shrink-0 text-orange-500" aria-hidden="true"></i>{{ $backup['name'] }}</span>
                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                    {{ $backup['client_name'] ?? __('Sin información') }} - {{ $backup['project_name'] ?? __('Sin proyecto') }}
                </span>
            </div>
        @endinteract

        @interact('column_database_type', $backup)
            {{ $backup['database_type'] === 'postgresql' ? __('PostgreSQL') : ucfirst($backup['database_type']) }}
        @endinteract

        @interact('column_extension', $backup)
            <span class="font-mono text-xs text-gray-600 dark:text-gray-300">.{{ $backup['extension'] }}</span>
        @endinteract

        @interact('column_last_modified', $backup)
            {{ $backup['last_modified']->format('d/m/Y H:i:s') }}
        @endinteract

        @interact('column_size', $backup)
            {{ number_format($backup['size'] / 1024 / 1024, 2) }} MB
        @endinteract

        @interact('column_action', $backup)
            @if($backup['client_code'] !== null)
                <a href="{{ route('clients.backups.download', ['clientCode' => $backup['client_code'], 'backup' => $backup['id']]) }}" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ __('Descargar') }}</a>
            @else
                <a href="{{ route('backups.download', ['path' => $backup['path']]) }}" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ __('Descargar') }}</a>
            @endif
        @endinteract

        <x-slot:empty>
            {{ __('Todavía no hay respaldos disponibles.') }}
        </x-slot:empty>
        </x-table>
    </div>
</div>
