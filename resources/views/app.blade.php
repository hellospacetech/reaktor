<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Favicons -->
        <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg">
        <link rel="manifest" href="/favicons/site.webmanifest">
        <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#000000">
        <link rel="shortcut icon" href="/favicons/favicon.ico">
        <meta name="msapplication-TileColor" content="#000000">
        <meta name="msapplication-config" content="/favicons/browserconfig.xml">
        <meta name="theme-color" content="#000000">

        <!-- Scripts -->
        @routes
        @vite(array_filter(\Nwidart\Modules\Module::getAssets(), fn($asset) => $asset !== 'resources/css/filament/admin/theme.css'))
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
