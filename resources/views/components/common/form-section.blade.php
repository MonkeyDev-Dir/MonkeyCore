<div>
    <!-- It is not the man who has too little, but the man who craves more, that is poor. - Seneca -->
</div>
@props([
    'separator' => true,
])

@if($separator)
    <hr class="my-6 border-gray-200 dark:border-gray-800" />
@endif

<section class="pt-0">
    {{ $slot }}
</section>
