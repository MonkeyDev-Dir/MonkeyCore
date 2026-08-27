<div class="rounded-xl border border-gray-200 bg-gray-100/80 p-5 dark:border-gray-800 dark:bg-gray-900/40">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Proyectos') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Administra los proyectos asociados a este cliente.') }}</p>
        </div>
        <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-project-create', { detail: { clientCode: '{{ $clientCode }}' } }))" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600"><span class="text-lg leading-none">+</span>{{ __('Nuevo proyecto') }}</button>
    </div>

    @if($client->projects->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 px-5 py-10 text-center dark:border-gray-700"><i data-lucide="folder-kanban" class="mx-auto h-10 w-10 text-gray-400" aria-hidden="true"></i><p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('No hay proyectos registrados para este cliente.') }}</p></div>
    @else
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @foreach($client->projects as $project)
                <article wire:key="client-project-{{ $project->id }}" x-data="{ expanded: false }" class="group relative rounded-lg border border-gray-200 bg-white p-3 transition hover:border-brand-400 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:hover:border-brand-500">
                    <div class="flex items-start gap-3">
                        <div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white dark:bg-brand-500 dark:text-white"><i data-lucide="folder-kanban" class="h-5 w-5" aria-hidden="true"></i></span><div class="min-w-0"><h3 class="truncate font-semibold text-gray-800 dark:text-white/90">{{ $project->name }}</h3>@if($project->code)<span class="mt-1 inline-flex items-center gap-1 rounded-md border border-brand-200 bg-brand-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-brand-800 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-400"><i data-lucide="hash" class="h-3 w-3" aria-hidden="true"></i>{{ $project->code }}</span>@endif</div></div>
                        <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-project-edit', { detail: { clientCode: '{{ $clientCode }}', projectId: {{ $project->id }} } }))" class="absolute right-3 top-3 rounded-lg p-2 text-gray-400 hover:bg-white hover:text-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500/40 dark:hover:bg-gray-800" aria-label="{{ __('Editar proyecto') }}" title="{{ __('Editar proyecto') }}"><i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i></button>
                    </div>
                    @if($project->description)
                        <div x-show="!expanded" class="mt-1 line-clamp-3 text-sm leading-6 text-gray-600 dark:text-gray-400">{{ $project->descriptionPreview() }}</div>
                        <div x-cloak x-show="expanded" class="prose prose-sm mt-1 max-w-none text-gray-600 dark:prose-invert dark:text-gray-400">{!! $project->description !!}</div>
                        @if($project->hasLongDescription())
                            <button type="button" x-on:click="expanded = !expanded" x-bind:aria-label="expanded ? '{{ __('Ocultar detalle') }}' : '{{ __('Ver detalle') }}'" class="mt-2 inline-flex items-center rounded-md p-1 text-brand-500 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:hover:bg-brand-500/10" title="{{ __('Ver detalle') }}">
                                <i x-show="!expanded" data-lucide="chevron-down" class="h-4 w-4" aria-hidden="true"></i>
                                <i x-cloak x-show="expanded" data-lucide="chevron-up" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                        @endif
                    @else
                        <p class="mt-1 text-sm italic text-gray-400 dark:text-gray-500">{{ __('Sin descripción') }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</div>
