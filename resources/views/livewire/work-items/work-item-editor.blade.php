<div x-data x-on:work-item-updated.window="Toast.fire({ icon: 'success', title: $event.detail.message })" x-on:work-item-follow-up-changed.window="$wire.refreshWorkItem()">
    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Información del caso') }}</h2>
            <div class="flex items-center gap-2">
                <button type="button" x-on:click="$tsui.open.modal('work-item-history-modal')" class="inline-flex items-center justify-center rounded-lg border border-gray-300 p-2 text-gray-600 hover:bg-gray-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" aria-label="{{ __('Ver historial') }}" title="{{ __('Ver historial') }}">
                    <i data-lucide="history" class="size-4" aria-hidden="true"></i>
                </button>
                <button type="button" x-on:click="$tsui.open.modal('work-item-edit-modal'); $wire.openEdit()" wire:loading.attr="disabled" wire:target="openEdit" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    <i wire:loading.remove wire:target="openEdit" data-lucide="pencil" class="size-4" aria-hidden="true"></i>
                    <span wire:loading wire:target="openEdit" class="size-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>
                    {{ __('Editar') }}
                </button>
            </div>
        </div>
        <dl class="mt-5 grid grid-cols-1 gap-x-8 gap-y-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Cliente') }}</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $workItem->client?->name ?? __('Interno') }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Proyecto') }}</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $workItem->project?->name ?? __('Sin proyecto') }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Creado por') }}</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $workItem->creator?->name }} {{ $workItem->creator?->lastname }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Fecha de creación') }}</dt><dd class="mt-1 font-medium text-gray-800 dark:text-white/90">{{ $workItem->created_at?->format('d/m/Y H:i') }}</dd></div>
            <div class="sm:col-span-2 lg:col-span-4">
                <dt class="text-gray-500 dark:text-gray-400">{{ __('Responsables') }}</dt>
                <dd class="mt-2 flex flex-wrap gap-2">
                    @forelse($workItem->assignees as $assignee)
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $assignee->name }} {{ $assignee->lastname }}</span>
                    @empty
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Sin responsables asignados') }}</span>
                    @endforelse
                </dd>
            </div>
        </dl>
    </section>

    <x-modal id="work-item-history-modal" size="3xl" center scrollable>
        <x-common.modal-close x-on:click="$tsui.close.modal('work-item-history-modal')" />
        <x-common.modal-header :title="__('Historial')" :description="__('Consulta la auditoría y los cambios registrados en el caso.')" />
        <div class="max-h-[calc(100vh-14rem)] space-y-4 overflow-y-auto px-4 py-5 custom-scrollbar sm:px-5">
            @forelse($workItem->events->sortByDesc('occurred_at') as $event)
                <div class="flex gap-3 border-l-2 border-brand-200 pl-4 dark:border-brand-500/30">
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $eventLabels[$event->event_type] ?? __('Actualización del caso') }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $event->actor?->name ?? __('Sistema') }} · {{ $event->occurred_at?->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No hay eventos registrados.') }}</p>
            @endforelse
        </div>
    </x-modal>
    <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Descripción') }}</h2>
            <button type="button" wire:click="saveDescription" wire:loading.attr="disabled" wire:target="saveDescription" class="rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="saveDescription">{{ __('Guardar descripción') }}</span>
                <span wire:loading wire:target="saveDescription">{{ __('Guardando...') }}</span>
            </button>
        </div>
        <div class="mt-4">
            <x-editor id="work-item-edit-description" wire:model="description" :toolbar="['style', 'bold', 'italic', 'underline', 'strikethrough', 'blockquote', 'unordered-list', 'ordered-list', 'undo', 'redo']" min-height="12rem" max-height="24rem" counters />
        </div>
    </section>

    <livewire:work-items.work-item-follow-up-composer :work-item="$workItem" />

    <x-common.form-modal id="work-item-edit-modal" :title="__('Editar información del caso')" :description="__('Actualiza la información general y los responsables del caso.')" size="4xl">
        <div wire:loading.flex wire:target="openEdit" class="absolute inset-0 z-10 items-center justify-center rounded-xl bg-white/70 backdrop-blur-[1px] dark:bg-gray-900/70">
            <span class="size-7 animate-spin rounded-full border-2 border-brand-500 border-t-transparent" aria-hidden="true"></span>
            <span class="sr-only">{{ __('Cargando información del caso') }}</span>
        </div>
        <form wire:submit="saveInformation" autocomplete="off" class="relative space-y-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-input id="work-item-edit-title" wire:model="title" type="text" :label="__('Título')" :error="$errors->first('title')" /></div>
                <x-select.native id="work-item-edit-type" wire:model.live="workItemTypeId" :label="__('Tipo')" :error="$errors->first('workItemTypeId')">
                    @foreach($types as $type)<option value="{{ $type->id }}">{{ __($type->name) }}</option>@endforeach
                </x-select.native>
                <x-select.native id="work-item-edit-category" wire:model="workItemCategoryId" :label="__('Categoría')" :error="$errors->first('workItemCategoryId')" :disabled="$workItemTypeId === null">
                    <option value="">{{ __('Sin categoría') }}</option>
                    @foreach($categories as $category)<option value="{{ $category->id }}">{{ __($category->name) }}</option>@endforeach
                </x-select.native>
                <x-select.native id="work-item-edit-client" wire:model.live="clientId" :label="__('Cliente')" :error="$errors->first('clientId')">
                    <option value="">{{ __('Interno') }}</option>
                    @foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach
                </x-select.native>
                <x-select.native id="work-item-edit-project" wire:model="projectId" :label="__('Proyecto')" :error="$errors->first('projectId')" :disabled="$clientId === null">
                    <option value="">{{ __('Sin proyecto') }}</option>
                    @foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach
                </x-select.native>
                <x-select.native id="work-item-edit-priority" wire:model="priority" :label="__('Prioridad')" :error="$errors->first('priority')">
                    <option value="low">{{ __('Baja') }}</option><option value="medium">{{ __('Media') }}</option><option value="high">{{ __('Alta') }}</option><option value="critical">{{ __('Crítica') }}</option>
                </x-select.native>
                <x-select.native id="work-item-edit-status" wire:model="status" :label="__('Estado')" :error="$errors->first('status')">
                    <option value="new">{{ __('Nuevo') }}</option><option value="assigned">{{ __('Asignado') }}</option><option value="under_analysis">{{ __('En análisis') }}</option><option value="waiting_for_customer">{{ __('Esperando cliente') }}</option><option value="waiting_for_third_party">{{ __('Esperando tercero') }}</option><option value="in_development">{{ __('En desarrollo') }}</option><option value="in_testing">{{ __('En pruebas') }}</option><option value="resolved">{{ __('Resuelto') }}</option><option value="closed">{{ __('Cerrado') }}</option><option value="cancelled">{{ __('Cancelado') }}</option>
                </x-select.native>
            </div>
            <div x-data="{
                search: '',
                open: false,
                normalize(value) {
                    return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
                },
                selected: @entangle('assigneeIds').live,
                toggle(id) {
                    if (this.selected.includes(id)) {
                        this.selected = this.selected.filter(selectedId => selectedId !== id);
                    } else {
                        this.selected = [...this.selected, id];
                    }

                    this.search = '';
                },
                remove(id) {
                    this.selected = this.selected.filter(selectedId => selectedId !== id);
                },
            }" x-on:click.outside="open = false">
                <label for="work-item-edit-assignees-search" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Responsables') }}</label>
                <div class="relative" x-on:click="open = true">
                    <div class="flex min-h-10 flex-wrap items-center gap-1.5 rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 focus-within:border-brand-500 dark:border-gray-700 dark:bg-transparent">
                        @foreach($users as $member)
                            <span x-show="selected.includes({{ $member->id }})" x-cloak class="inline-flex items-center gap-1 rounded-md bg-brand-100 px-2 py-1 text-xs font-medium text-brand-800 dark:bg-brand-500/10 dark:text-brand-400">
                                {{ $member->name }} {{ $member->lastname }}
                                <button type="button" x-on:click="remove({{ $member->id }})" class="rounded p-0.5 hover:bg-brand-200 dark:hover:bg-brand-500/20" aria-label="{{ __('Quitar responsable') }}">&times;</button>
                            </span>
                        @endforeach
                        <input id="work-item-edit-assignees-search" x-model="search" x-on:click="open = true" x-on:focus="open = true" x-on:keydown.escape="open = false" type="search" autocomplete="off" placeholder="{{ __('Buscar responsable...') }}" class="min-w-32 flex-1 border-0 bg-transparent px-0 py-1 text-sm text-gray-800 outline-none focus:ring-0 dark:text-white/90 dark:placeholder:text-gray-500" />
                    </div>
                    <div x-show="open" x-cloak class="absolute z-20 mt-1 max-h-52 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                        @foreach($users as $member)
                            <button type="button" wire:key="work-item-edit-member-{{ $member->id }}" x-on:click="toggle({{ $member->id }})" x-show="normalize({{ Js::from($member->name.' '.$member->lastname) }}).includes(normalize(search))" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <span>{{ $member->name }} {{ $member->lastname }}</span>
                                <span x-show="selected.includes({{ $member->id }})" class="text-brand-500">✓</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                @if($users->isEmpty())
                    <small class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ __('No hay usuarios registrados') }}</small>
                @endif
                @error('assigneeIds')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
                @error('assigneeIds.*')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
            </div>
            <x-common.form-actions>
                <button type="button" x-on:click="$tsui.close.modal('work-item-edit-modal')" wire:click="close" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">{{ __('Cancelar') }}</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveInformation" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50"><span wire:loading.remove wire:target="saveInformation">{{ __('Guardar cambios') }}</span><span wire:loading wire:target="saveInformation">{{ __('Guardando...') }}</span></button>
            </x-common.form-actions>
        </form>
    </x-common.form-modal>
</div>









