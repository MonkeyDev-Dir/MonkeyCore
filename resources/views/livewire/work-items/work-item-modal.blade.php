<div>
    <x-common.form-modal id="work-item-modal" :title="__('Nuevo caso')" :description="__('Registra un trabajo para la Mesa de trabajo.')" size="4xl"
        x-on:open-work-item-create.window="$wire.openCreate()"
        x-on:work-item-saved.window="Toast.fire({ icon: 'success', title: $event.detail.message })">
        <form wire:submit="save" autocomplete="off" class="space-y-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-select.native id="work-item-type" wire:model.live="workItemTypeId" :label="__('Tipo')" :error="$errors->first('workItemTypeId')">
                        <option value="">{{ __('Selecciona un tipo') }}</option>
                        @foreach($types as $type)
                            <option wire:key="work-item-type-{{ $type->id }}" value="{{ $type->id }}">{{ __($type->name) }}</option>
                        @endforeach
                    </x-select.native>
                </div>
                <div>
                    <x-select.native id="work-item-category" wire:model="workItemCategoryId" :label="__('Categoría')" :error="$errors->first('workItemCategoryId')" :disabled="$workItemTypeId === null">
                        <option value="">{{ __('Selecciona una categoría') }}</option>
                        @foreach($categories as $category)
                            <option wire:key="work-item-category-{{ $category->id }}" value="{{ $category->id }}">{{ __($category->name) }}</option>
                        @endforeach
                    </x-select.native>
                </div>
                <div>
                    <x-select.native id="work-item-priority" wire:model="priority" :label="__('Prioridad')" :error="$errors->first('priority')">
                        <option value="low">{{ __('Baja') }}</option>
                        <option value="medium">{{ __('Media') }}</option>
                        <option value="high">{{ __('Alta') }}</option>
                        <option value="critical">{{ __('Crítica') }}</option>
                    </x-select.native>
                </div>
                <div>
                    <x-select.native id="work-item-client" wire:model.live="clientId" :label="__('Cliente')" :error="$errors->first('clientId')">
                        <option value="">{{ __('Sin cliente') }}</option>
                        @foreach($clients as $client)
                            <option wire:key="work-item-client-{{ $client->id }}" value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select.native>
                </div>
                <div>
                    <x-select.native id="work-item-project" wire:model.live="projectId" :label="__('Proyecto')" :error="$errors->first('projectId')" :disabled="$clientId === null">
                        <option value="">{{ __('Sin proyecto') }}</option>
                        @foreach($projects as $project)
                            <option wire:key="work-item-project-{{ $project->id }}" value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
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
                    <label for="work-item-assignees-search" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Responsables') }}</label>
                    <div class="relative" x-on:click="open = true">
                        <div class="flex min-h-10 flex-wrap items-center gap-1.5 rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 focus-within:border-brand-500 dark:border-gray-700 dark:bg-transparent">
                            @foreach($users as $member)
                                <span x-show="selected.includes({{ $member->id }})" x-cloak class="inline-flex items-center gap-1 rounded-md bg-brand-100 px-2 py-1 text-xs font-medium text-brand-800 dark:bg-brand-500/10 dark:text-brand-400">
                                    {{ $member->name }} {{ $member->lastname }}
                                    <button type="button" x-on:click="remove({{ $member->id }})" class="rounded p-0.5 hover:bg-brand-200 dark:hover:bg-brand-500/20" aria-label="{{ __('Quitar responsable') }}">&times;</button>
                                </span>
                            @endforeach
                            <input id="work-item-assignees-search" x-model="search" x-on:click="open = true" x-on:focus="open = true" x-on:keydown.escape="open = false" type="search" autocomplete="off" placeholder="{{ __('Buscar responsable...' ) }}" class="min-w-32 flex-1 border-0 bg-transparent px-0 py-1 text-sm text-gray-800 outline-none focus:ring-0 dark:text-white/90 dark:placeholder:text-gray-500" />
                        </div>
                        <div x-show="open" x-cloak class="absolute z-20 mt-1 max-h-52 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                            @foreach($users as $member)
                                <button type="button" wire:key="work-item-member-{{ $member->id }}" x-on:click="toggle({{ $member->id }})" x-show="normalize({{ Js::from($member->name.' '.$member->lastname) }}).includes(normalize(search))" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
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
                <div class="sm:col-span-2">
                    <x-input id="work-item-title" wire:model="title" type="text" autocomplete="off" :label="__('Título')" />
                </div>
                <div class="sm:col-span-2">
                    <x-editor id="work-item-description" wire:model="description" :label="__('Descripción')" :toolbar="['style', 'bold', 'italic', 'underline', 'strikethrough', 'blockquote', 'unordered-list', 'ordered-list', 'undo', 'redo']" min-height="12rem" max-height="24rem" counters />
                </div>
            </div>
            <x-common.form-actions>
                <button type="button" x-on:click="$tsui.close.modal('work-item-modal')" wire:click="close" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">{{ __('Cancelar') }}</button>
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">{{ __('Crear caso') }}</span>
                    <span wire:loading wire:target="save">{{ __('Creando...') }}</span>
                </button>
            </x-common.form-actions>
        </form>
    </x-common.form-modal>
</div>
