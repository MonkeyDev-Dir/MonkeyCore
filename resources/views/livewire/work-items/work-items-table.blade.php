<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-900 dark:bg-gray-950">
    <div class="flex flex-col gap-4 border-b border-gray-200 px-4 py-4 dark:border-gray-900 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Casos creados') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Consulta y da seguimiento a los casos registrados.') }}</p>
        </div>
        <div class="w-full sm:max-w-sm">
            <x-input
                id="work-items-search"
                wire:model.live.debounce.500ms="search"
                type="search"
                autocomplete="off"
                :label="__('Buscar')"
                :placeholder="__('Buscar por código, título o empresa...')"
            />
        </div>
    </div>
    <x-table
        :headers="$headers"
        :rows="$workItems"
        :sort="$sort"
        paginate
        :on-each-side="0"
    >
        @interact('column_case', $workItem)
            <div class="flex min-w-0 items-center gap-2">
                <span class="size-2 shrink-0 rounded-full {{ $workItem->priority->color() }}" title="{{ __('Prioridad: :priority', ['priority' => $workItem->priority->label()]) }}" aria-label="{{ __('Prioridad del caso') }}"></span>
                <div class="min-w-0">
                    <a href="{{ route('work-items.show', ['publicCode' => $workItem->public_code]) }}" class="max-w-xs truncate font-medium text-gray-800 hover:text-brand-600 hover:underline dark:text-white/90 dark:hover:text-brand-400" title="{{ $workItem->title }}">{{ \Illuminate\Support\Str::limit($workItem->title, 30, '...' ) }}</a>
                    <div class="max-w-xs truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $workItem->client?->name ?? __('Interno') }}">{{ $workItem->client?->name ?? __('Interno') }}</div>
                </div>
            </div>
        @endinteract
        @interact('column_type_name', $workItem)
            {{ __($workItem->type?->name ?? __('Sin tipo')) }}
        @endinteract
        @interact('column_status', $workItem)
            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $workItem->status->color() }}">
                {{ $workItem->status->label() }}
            </span>
        @endinteract
        @interact('column_created_at', $workItem)
            {{ $workItem->created_at?->format('d/m/Y H:i') }}
        @endinteract
        <x-slot:empty>{{ __('Todavía no hay casos creados.') }}</x-slot:empty>
    </x-table>
</div>