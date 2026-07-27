{{--
    GC-Stats — Broadcast widget layout

    Minimal chrome-less layout for standalone overlay pages (OBS Browser
    Source): transparent background, no nav/footer/search, just the Vite
    assets so the widget's Blade/Alpine/Tailwind markup renders identically
    to the rest of the site.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('head_to_head.title'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        html, body { background: transparent !important; height: 100%; margin: 0; overflow: hidden; }
    </style>
</head>
<body class="w-screen h-screen">
    @yield('content')

    @livewireScripts
</body>
</html>
