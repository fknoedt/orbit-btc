<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    @section('head')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orbit BTC</title>
    <meta name="description" content="Orbit lets you combine on-chain metrics and market indicators into a formula, then monitor it. Weight it, set a threshold, and backtest how it would have performed.">
    <meta name="keywords" content="Bitcoin, BTC, Signals, Analysis">
    <meta name="theme-color" content="#07080d">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/orbit-btc.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite([
        'resources/css/landing.css',
    ])
    @show
</head>
<body class="ol">
<a class="ol-skip" href="#home">Skip to content</a>

@yield('content')

@vite([
    'resources/js/landing/gumshoe.polyfills.min.js',
    'resources/js/landing/app.js'
])

@yield('scripts')
</body>
</html>
