<div class="space-y-6">
    <div>
        <label for="client-backup-project" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Proyecto') }}</label>
        <select id="client-backup-project" wire:model.live="projectId" class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 sm:max-w-md">
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
