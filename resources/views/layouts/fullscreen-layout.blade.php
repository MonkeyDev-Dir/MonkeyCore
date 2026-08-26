<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-theme-context="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Iniciar sesión' }} | {{ config('app.name', 'MonkeyCore') }}</title>

    @vite(['resources/css/app.css', 'resources/sass/app.scss', 'resources/js/app.js'])

    <script>
        (() => {
            document.documentElement.classList.remove('dark');
        })();
    </script>
</head>

<body x-data="{ loaded: true }" class="h-full">
    <x-common.preloader />

    @yield('content')

    @stack('scripts')
</body>

</html>
