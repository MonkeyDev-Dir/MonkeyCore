<div>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
        <form wire:submit="consult" autocomplete="off" class="space-y-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-12">
                <div class="sm:col-span-4">
                    <label for="civil-consultation-type" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tipo de identificación') }}</label>
                    <select id="civil-consultation-type" wire:model.live="type" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="person">{{ __('Cédula física') }}</option>
                        <option value="juridica">{{ __('Cédula jurídica') }}</option>
                    </select>
                    @error('type')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
                </div>
                <div class="sm:col-span-8">
                    <label for="civil-consultation-identification" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $type === 'person' ? __('Número de cédula') : __('Número de cédula jurídica') }}
                    </label>
                    <input id="civil-consultation-identification" wire:model="identification" type="text" inputmode="numeric" autocomplete="off"
                        placeholder="{{ $type === 'person' ? __('Ingrese 9 dígitos') : __('Ingrese 10 dígitos') }}"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-gray-500" />
                    @error('identification')<small class="mt-1 block text-xs text-red-500">{{ $message }}</small>@enderror
                </div>
            </div>

            @error('lookup')
                <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="consult" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
                    <x-icon name="magnifying-glass" class="size-4" />
                    <span wire:loading.remove wire:target="consult">{{ __('Consultar') }}</span>
                    <span wire:loading wire:target="consult">{{ __('Consultando...') }}</span>
                </button>
            </div>
        </form>
    </section>

    @if($result !== null)
        <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <div class="space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Resultado de la consulta') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Información obtenida del registro civil.') }}</p>
                </div>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach($resultFields as $field)
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $field['label'] }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $field['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif
</div>
