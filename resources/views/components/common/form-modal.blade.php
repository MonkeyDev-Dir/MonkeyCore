<div>
    <!-- Let all your things have their places; let each part of your business have its time. - Benjamin Franklin -->
</div>
@props([
    'id',
    'title',
    'description' => null,
    'size' => '2xl',
    'wireState' => 'isOpen',
])

<div x-data {{ $attributes->whereStartsWith('x-on:') }}>
    <x-modal :id="$id" :wire="$wireState" z-index="z-[70]" :size="$size" center x-on:close="$wire.close()">
        <x-common.modal-close x-on:click="$tsui.close.modal('{{ $id }}')" wire:click="close" />
        <x-common.modal-header :$title :$description />
        <div x-ref="modalContent" class="max-h-[calc(100vh-12rem)] overflow-y-auto pr-1 custom-scrollbar sm:max-h-[calc(80vh-8rem)]">
            {{ $slot }}
        </div>
    </x-modal>
</div>
