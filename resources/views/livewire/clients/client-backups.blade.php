<div class="space-y-6">
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
                    <button type="button" wire:key="backup-connection-{{ $connection->id }}" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-backup-connection-edit', { detail: { clientCode: '{{ $clientCode }}', connectionId: {{ $connection->id }} } }))" class="w-full rounded-lg border border-gray-200 bg-white p-3 text-left transition hover:border-brand-400 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500/40 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-brand-500"><div class="flex items-center justify-between gap-3"><span class="truncate text-sm font-medium text-gray-800 dark:text-white/90">{{ $connection->name }}</span><span @class(['inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium', 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400' => $connection->is_active, 'border-gray-200 bg-gray-100 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400' => ! $connection->is_active])>{{ __($connection->is_active ? 'Activa' : 'Inactiva') }}</span></div><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $connection->project?->name ?? __('Todos los proyectos') }} · {{ $connection->ssh_host }}</p></button>
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
