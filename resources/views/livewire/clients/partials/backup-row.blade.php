<div class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
    <div class="min-w-0">
        <p class="flex items-center gap-2 truncate text-sm font-medium text-gray-800 dark:text-white/90">
            <i data-lucide="database" class="h-4 w-4 shrink-0 text-orange-500" aria-hidden="true"></i>
            <span class="truncate">{{ $backup->filename ?: __('Respaldo') }}</span>
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ $backup->backupConnection?->name ?? __('Respaldo') }} ·
            {{ $backup->generated_at?->format('d/m/Y H:i') }}
            @if($backup->project)
                · {{ $backup->project->name }}
            @endif
            @if($backup->size !== null)
                · {{ number_format($backup->size / 1024 / 1024, 2) }} MB
            @endif
        </p>
    </div>
    <a href="{{ route('clients.backups.download', ['clientCode' => $clientCode, 'backup' => $backup->id]) }}" class="shrink-0 text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
        {{ __('Descargar') }}
    </a>
</div>
