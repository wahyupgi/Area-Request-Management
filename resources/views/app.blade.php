<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/jpeg" href="/logo-pgi.jpg">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />

        <script>
            (function() {
                var stored = localStorage.getItem('area-request-theme');
                var isLight = stored === 'light' || (!stored && window.matchMedia('(prefers-color-scheme: light)').matches);
                if (isLight) {
                    document.documentElement.classList.add('theme-light');
                    document.documentElement.classList.remove('dark');
                    document.documentElement.dataset.theme = 'light';
                } else {
                    document.documentElement.classList.remove('theme-light');
                    document.documentElement.classList.add('dark');
                    document.documentElement.dataset.theme = 'dark';
                }
            })();
        </script>

        <!-- Scripts -->
        @routes
        @vite('resources/js/app.js')
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
