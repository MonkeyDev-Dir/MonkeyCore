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
                <div>
                    <label for="work-item-assignees" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Responsables') }}</label>
                    <select id="work-item-assignees" wire:model="assigneeIds" multiple class="min-h-24 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90" @disabled($projectId === null)>
                        @forelse($members as $member)
                            <option wire:key="work-item-member-{{ $member->id }}" value="{{ $member->id }}">{{ $member->name }} {{ $member->lastname }}</option>
                        @empty
                            <option disabled>{{ $projectId === null ? __('Selecciona un proyecto') : __('No hay miembros asociados al proyecto') }}</option>
                        @endforelse
                    </select>
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
