<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Iniciar sesión' }} | {{ config('app.name', 'MonkeyCore') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (() => {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

            document.documentElement.classList.toggle('dark', (savedTheme || systemTheme) === 'dark');
        })();
    </script>
</head>

<body x-data="{ loaded: true }" class="h-full">
    <x-common.preloader />

    @yield('content')

    @stack('scripts')
</body>

</html>
