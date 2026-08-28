<div class="rounded-xl border border-gray-200 bg-gray-100/80 p-5 dark:border-gray-800 dark:bg-gray-900/40">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('Dominios') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Administra los dominios, hospedaje, costos y fechas de renovación.') }}</p>
        </div>
        <button type="button" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-domain-create', { detail: { clientCode: '{{ $clientCode }}' } }))" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600"><span class="text-lg leading-none">+</span>{{ __('Nuevo dominio') }}</button>
    </div>

    @if($client->domains->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 px-5 py-10 text-center dark:border-gray-700"><i data-lucide="globe-2" class="mx-auto h-10 w-10 text-gray-400" aria-hidden="true"></i><p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('No hay dominios registrados para este cliente.') }}</p></div>
    @else
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @foreach($client->domains as $domain)
                <article wire:key="client-domain-{{ $domain->id }}" class="relative rounded-lg border border-gray-200 bg-white p-4 transition hover:border-brand-400 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:hover:border-brand-500">
                    <x-common.edit-button :label="__('Editar dominio')" x-data x-on:click="window.dispatchEvent(new CustomEvent('open-domain-edit', { detail: { clientCode: '{{ $clientCode }}', domainId: {{ $domain->id }} } }))" class="absolute right-3 top-3" />
                    <div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white"><i data-lucide="globe-2" class="h-5 w-5" aria-hidden="true"></i></span><div class="flex min-w-0 items-center gap-1"><h3 class="truncate font-semibold text-gray-800 dark:text-white/90">{{ $domain->name }}</h3>@if(! $domain->isHostedAtDonDominio())<span x-data x-tooltip="{{ __('Pendiente de migración a DonDominio') }}" data-tooltip-color="orange" tabindex="0" aria-label="{{ __('Pendiente de migración a DonDominio') }}" class="inline-flex shrink-0 cursor-help text-orange-500 outline-none focus-visible:ring-2 focus-visible:ring-orange-400 dark:text-orange-400"><i data-lucide="triangle-alert" class="h-4 w-4" aria-hidden="true"></i></span>@endif</div></div>
                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm"><div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Hospedaje') }}</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $domain->hosting_provider ?? __('Sin información') }}</dd></div><div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Vence') }}</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $domain->expires_at->format('d/m/Y') }}</dd></div><div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Pago anual') }}</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ $domain->annual_cost !== null ? number_format((float) $domain->annual_cost, 2).' '.$domain->currency : __('Sin información') }}</dd></div><div><dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('Renovación') }}</dt><dd class="mt-1 font-medium text-gray-700 dark:text-gray-300">{{ trans_choice(':years año|:years años', $domain->renewal_period_years, ['years' => $domain->renewal_period_years]) }}</dd></div></dl>
                    @if($domain->notes)<p class="mt-4 border-t border-gray-100 pt-3 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">{{ $domain->notes }}</p>@endif
                </article>
            @endforeach
        </div>
    @endif
</div>
