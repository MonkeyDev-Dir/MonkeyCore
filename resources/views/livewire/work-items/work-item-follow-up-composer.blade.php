<div x-data x-on:close-follow-up-modal.window="$tsui.close.modal('work-item-follow-up-modal')">
    <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Seguimiento') }}</h2>
                    <span class="inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">{{ $followUpCount }} {{ $followUpCount === 1 ? __('actualización') : __('actualizaciones') }}</span>
                    <span class="inline-flex items-center rounded-full bg-orange-50 px-2 py-0.5 text-[10px] font-semibold text-orange-700 dark:bg-orange-500/10 dark:text-orange-400">{{ number_format($totalEffectiveHours, 2) }} {{ __('h efectivas') }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Registra el trabajo realizado y los avances del caso.') }}</p>
            </div>
            <button type="button" x-on:click="$tsui.open.modal('work-item-follow-up-modal'); $wire.openCreate()" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white hover:bg-brand-600" aria-label="{{ __('Agregar seguimiento') }}">
                <i data-lucide="plus" class="size-4" aria-hidden="true"></i>
                {{ __('Agregar seguimiento') }}
            </button>
        </div>

        <div class="mt-6 space-y-4">
            @forelse($followUps as $followUp)
                <article wire:key="work-item-follow-up-{{ $followUp->id }}" x-on:click="$tsui.open.modal('work-item-follow-up-modal'); $wire.openEdit({{ $followUp->id }})" class="relative cursor-pointer rounded-lg border border-gray-200 p-2.5 transition hover:border-brand-300 hover:bg-brand-50/30 dark:border-gray-700 dark:hover:border-brand-500/40 dark:hover:bg-brand-500/5">
                    <div class="pr-8 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-[10px] leading-3 text-gray-500 dark:text-gray-400">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $followUp->user->name }} {{ $followUp->user->lastname }}</span>
                        <span aria-hidden="true">·</span>
                        <time datetime="{{ $followUp->created_at?->toIso8601String() }}">{{ $followUp->created_at?->format('d/m/Y H:i') }}</time>
                        @if($followUp->effective_hours !== null)
                            <span aria-hidden="true">·</span>
                            <span>{{ number_format((float) $followUp->effective_hours, 2) }} {{ __('h efectivas') }}</span>
                        @endif
                    </div>
                    <x-common.edit-button :label="__('Editar seguimiento')" x-on:click.stop="$tsui.open.modal('work-item-follow-up-modal'); $wire.openEdit({{ $followUp->id }})" class="absolute right-1.5 top-1 p-0.5" />
                    <div class="mt-2 flex items-start gap-1.5 text-xs leading-4 text-gray-600 dark:text-gray-400">
                        <i data-lucide="arrow-right" class="mt-0.5 size-3.5 shrink-0 text-orange-500" aria-hidden="true"></i>
                        <span class="line-clamp-2" title="{{ $followUp->content }}">{{ $this->excerpt($followUp) }}</span>
                    </div>
                    @if($followUp->attachments->isNotEmpty())
                        <div class="mt-1 flex items-center gap-1 text-[10px] font-medium text-brand-600 dark:text-brand-400">
                            <i data-lucide="paperclip" class="size-3.5" aria-hidden="true"></i>
                            {{ trans_choice(':count imagen|:count imágenes', $followUp->attachments->count(), ['count' => $followUp->attachments->count()]) }}
                        </div>
                    @endif
                </article>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Todavía no hay seguimientos registrados.') }}</p>
            @endforelse
        </div>
    </section>

    <x-common.form-modal id="work-item-follow-up-modal" :title="$editingFollowUpId === null ? __('Agregar seguimiento') : __('Editar seguimiento')" :description="__('Registra el trabajo realizado y los avances del caso.')" size="4xl">
        <div wire:loading.flex wire:target="openEdit" class="absolute inset-0 z-10 items-center justify-center rounded-xl bg-white/70 backdrop-blur-[1px] dark:bg-gray-900/70">
            <span class="size-7 animate-spin rounded-full border-2 border-brand-500 border-t-transparent" aria-hidden="true"></span>
            <span class="sr-only">{{ __('Cargando información del seguimiento') }}</span>
        </div>
        <form wire:submit="save" class="relative space-y-5">
            <div class="w-full sm:w-44 sm:ml-auto">
                <label for="work-item-follow-up-effective-hours" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Horas efectivas (opcional)') }}</label>
                <input id="work-item-follow-up-effective-hours" wire:model="effectiveHours" type="number" min="0" max="999999.99" step="0.25" inputmode="decimal" placeholder="0.00" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-gray-500" />
                @error('effectiveHours')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
            </div>
            <x-editor id="work-item-follow-up-content" wire:model="content" upload-property="followUpImage" upload-method="storeFollowUpImage" :upload-mimes="['image/jpeg', 'image/png', 'image/gif', 'image/webp']" upload-max-size="5120" :label="__('Actualización')" :toolbar="['style', 'bold', 'italic', 'underline', 'strikethrough', 'blockquote', 'unordered-list', 'ordered-list', 'code', 'code-block', 'link', 'image', 'undo', 'redo']" min-height="10rem" max-height="24rem" counters x-on:paste.capture="if ([...$event.clipboardData.files].some(file => file.type.startsWith('image/'))) { $event.preventDefault(); $event.stopPropagation(); const image = [...$event.clipboardData.files].find(file => file.type.startsWith('image/')); uploadImage(image); const insertUploadedImage = () => { if (uploading) { window.setTimeout(insertUploadedImage, 100); } else if (imageUrl) { insertImage(); } }; window.setTimeout(insertUploadedImage, 100); }" />
            @error('content')<small class="block text-xs text-red-500">{{ $message }}</small>@enderror
            @error('pendingAttachmentIds.*')<small class="block text-xs text-red-500">{{ $message }}</small>@enderror
            <x-common.form-actions>
                <button type="button" x-on:click="$tsui.close.modal('work-item-follow-up-modal'); $wire.close()" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">{{ __('Cancelar') }}</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">{{ __('Guardar seguimiento') }}</span>
                    <span wire:loading wire:target="save">{{ __('Guardando...') }}</span>
                </button>
            </x-common.form-actions>
        </form>
    </x-common.form-modal>
</div>













