<section>
    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Histórico anual') }}</h3>

    <div class="mt-4 space-y-4">
        @forelse($groups as $group)
            <div wire:key="annual-backup-group-{{ $group['year']->format('Y') }}">
                <h4 class="mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $group['year']->format('Y') }}</h4>

                <x-table :headers="$headers" :rows="$group['backups']">
                    @interact('column_month', $backup)
                        {{ $backup->generated_at?->format('m/Y') }}
                    @endinteract

                    @interact('column_filename', $backup)
                        <span class="flex items-center gap-2 font-medium text-gray-800 dark:text-white/90">
                            <i data-lucide="database" class="h-4 w-4 shrink-0 text-orange-500" aria-hidden="true"></i>
                            {{ $backup->filename }}
                        </span>
                    @endinteract

                    @interact('column_connection', $backup)
                        {{ $backup->backupConnection?->name ?? __('Respaldo') }}
                    @endinteract

                    @interact('column_generated_at', $backup)
                        {{ $backup->generated_at?->format('d/m/Y') }}
                    @endinteract

                    @interact('column_size', $backup)
                        {{ $backup->size !== null ? number_format($backup->size / 1024 / 1024, 2).' MB' : __('Sin información') }}
                    @endinteract

                    @interact('column_action', $backup, $clientCode)
                        <a href="{{ route('clients.backups.download', ['clientCode' => $clientCode, 'backup' => $backup->id]) }}" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                            {{ __('Descargar') }}
                        </a>
                    @endinteract
                </x-table>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                {{ __('No hay respaldos anuales disponibles.') }}
            </div>
        @endforelse
    </div>
</section>
