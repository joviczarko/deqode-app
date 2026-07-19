<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-deqode-wrapper="1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @stack('head')

    {{-- Placeholder: GA4 / Meta Pixel head tags (Chunk 3b) --}}
    @stack('analytics-head')
</head>
<body>
    {{-- Placeholder: analytics body-start (Chunk 3b) --}}
    @stack('analytics-body-start')

    @yield('body')

    {{-- Placeholder: analytics body-end / noscript (Chunk 3b) --}}
    @stack('analytics-body-end')
</body>
</html>
