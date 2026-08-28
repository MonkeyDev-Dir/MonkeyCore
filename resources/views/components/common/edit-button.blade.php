@props(['label'])

<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500/40 dark:hover:bg-gray-950',
    'aria-label' => $label,
    'title' => $label,
]) }}>
    <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
</button>
