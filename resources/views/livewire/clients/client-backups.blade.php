<div class="space-y-6" x-data x-on:backup-queued.window="Toast.fire({ icon: 'success', title: $event.detail.message })" x-on:backup-queue-warning.window="Toast.fire({ icon: 'warning', title: $event.detail.message })" x-on:backup-queue-failed.window="Toast.fire({ icon: 'error', title: $event.detail.message })">
    <div class="rounded-xl border border-gray-200 bg-gray-100/80 p-5 dark:border-gray-800 dark:bg-gray-900/40">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Configuraciones de respaldo') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Administra las conexiones que generan respaldos para este cliente.') }}</p>
            </div>
            <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-backup-connection-create', { detail: { clientCode: '{{ $clientCode }}' } }))" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600"><span class="text-lg leading-none">+</span>{{ __('Nueva configuración') }}</button>
        </div>
        @if($client->backupConnections->isEmpty())
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('No hay configuraciones de respaldo registradas.') }}</p>
        @else
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                @foreach($client->backupConnections as $connection)
                    <div wire:key="backup-connection-{{ $connection->id }}" class="rounded-lg border border-gray-200 bg-white p-3 transition hover:border-brand-400 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:hover:border-brand-500">
                        <div class="flex items-start justify-between gap-3">
                            <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-backup-connection-edit', { detail: { clientCode: '{{ $clientCode }}', connectionId: {{ $connection->id }} } }))" class="min-w-0 flex-1 text-left focus:outline-none focus:ring-2 focus:ring-brand-500/40">
                                <div class="flex items-center gap-2"><span class="truncate text-sm font-medium text-gray-800 dark:text-white/90">{{ $connection->name }}</span><span @class(['inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-xs font-medium', 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400' => $connection->is_active, 'border-gray-200 bg-gray-100 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400' => ! $connection->is_active])>{{ __($connection->is_active ? 'Activa' : 'Inactiva') }}</span></div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $connection->project?->name ?? __('Todos los proyectos') }} · {{ $connection->ssh_host }}</p>
                            </button>
                            <button type="button" wire:click="queueBackup({{ $connection->id }})" wire:loading.attr="disabled" wire:target="queueBackup({{ $connection->id }})" @disabled(! $connection->is_active) class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-brand-200 text-brand-600 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-brand-500/30 dark:text-brand-400 dark:hover:bg-brand-500/10" title="{{ __('Ejecutar ahora') }}" aria-label="{{ __('Ejecutar ahora') }}">
                                <i data-lucide="play" wire:loading.remove wire:target="queueBackup({{ $connection->id }})" class="h-4 w-4" aria-hidden="true"></i>
                                <i data-lucide="loader-circle" wire:loading wire:target="queueBackup({{ $connection->id }})" class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <label for="client-backup-project" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Proyecto') }}</label>
        <select id="client-backup-project" wire:model.live="projectId" class="h-10 w-full rounded-lg border border-gray-300 bg-gray-100 px-3 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 sm:max-w-md">
            <option value="">{{ __('Todos los proyectos') }}</option>
            @foreach($client->projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>

    <livewire:clients.client-weekly-backups :client-code="$clientCode" :project-id="$projectId" wire:key="weekly-backups-{{ $clientCode }}-{{ $projectId ?? 'all' }}" />

    @if($showHistory)
        <livewire:clients.client-monthly-backups :client-code="$clientCode" :project-id="$projectId" wire:key="monthly-backups-{{ $clientCode }}-{{ $projectId ?? 'all' }}" />
        <livewire:clients.client-annual-backups :client-code="$clientCode" :project-id="$projectId" wire:key="annual-backups-{{ $clientCode }}-{{ $projectId ?? 'all' }}" />
    @endif
</div>
