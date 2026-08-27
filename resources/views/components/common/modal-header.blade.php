@props([
    'title',
    'description' => null,
])

<div class="mb-6 pr-10">
    <h2 id="modal-title" class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h2>

    @if($description)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif
</div>
