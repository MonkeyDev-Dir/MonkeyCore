<section>
    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Histórico de respaldos') }}</h3>

    <div class="mt-4 space-y-3">
        @forelse($groups as $group)
            <details class="group rounded-xl border border-gray-200 dark:border-gray-800">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 text-sm font-medium text-gray-700 marker:hidden dark:text-gray-300">
                    <span>{{ $group['month']->format('m/Y') }}</span>
                    <svg class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </summary>
                <div class="space-y-3 border-t border-gray-200 p-3 dark:border-gray-800">
                    @foreach($group['backups'] as $backup)
                        @include('livewire.clients.partials.backup-row', ['backup' => $backup, 'clientCode' => $clientCode])
                    @endforeach
                </div>
            </details>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No hay respaldos históricos disponibles.') }}</p>
        @endforelse
    </div>
</section>
