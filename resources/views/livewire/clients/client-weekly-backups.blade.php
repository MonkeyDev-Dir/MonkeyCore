<section>
    <div class="flex items-center justify-between gap-4">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Respaldos de esta semana') }}</h3>
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Archivo') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Conexión') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Hora') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Tamaño') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
        @forelse($groups as $group)
                <tr class="bg-gray-50/70 dark:bg-white/[0.02]">
                    <th colspan="5" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $group['date']->format('d/m/Y') }}</th>
                </tr>
                @foreach($group['backups'] as $backup)
                    <tr wire:key="weekly-backup-{{ $backup->id }}" class="text-gray-700 dark:text-gray-300">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-800 dark:text-white/90">
                            <span class="flex items-center gap-2">
                                <i data-lucide="database" class="h-4 w-4 shrink-0 text-orange-500" aria-hidden="true"></i>
                                {{ $backup->filename }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $backup->backupConnection?->name ?? __('Respaldo') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $backup->generated_at?->format('H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $backup->size !== null ? number_format($backup->size / 1024 / 1024, 2).' MB' : __('Sin información') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('clients.backups.download', ['clientCode' => $clientCode, 'backup' => $backup->id]) }}" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">{{ __('Descargar') }}</a>
                        </td>
                    </tr>
                @endforeach
        @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No hay respaldos generados esta semana.') }}</td>
                </tr>
        @endforelse
            </tbody>
        </table>
    </div>
</section>
