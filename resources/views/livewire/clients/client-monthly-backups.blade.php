<section>
    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ __('Histórico de respaldos') }}</h3>

    <div class="mt-4 space-y-3">
        @forelse($groups as $group)
            <x-accordion multiple bordered>
                <x-accordion.items
                    :title="$group['month']->format('m/Y')"
                    id="monthly-backups-{{ $group['month']->format('Y-m') }}"
                >
                    <div class="space-y-3">
                        @foreach($group['backups'] as $backup)
                            @include('livewire.clients.partials.backup-row', ['backup' => $backup, 'clientCode' => $clientCode])
                        @endforeach
                    </div>
                </x-accordion.items>
            </x-accordion>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No hay respaldos históricos disponibles.') }}</p>
        @endforelse
    </div>
</section>
