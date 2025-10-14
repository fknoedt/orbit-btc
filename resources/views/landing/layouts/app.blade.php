<!DOCTYPE html>
<html lang="en" class="light scroll-smooth" dir="ltr">
<head>
    @section('head')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orbit BTC</title>
    <meta name="description" content="BTC Analysis Signals">
    <meta name="keywords" content="Bitcoin, BTC, Signals, Analysis">
    <meta name="version" content="1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- favicon -->
    <link rel="icon" href="{{ asset('images/orbit-btc.ico') }}">
    <link href="assets/libs/tobii/css/tobii.min.css" rel="stylesheet">
    <link href="assets/libs/tiny-slider/tiny-slider.css" rel="stylesheet">
    <!-- Main Css -->
    <link href="assets/libs/@iconscout/unicons/css/line.css" type="text/css" rel="stylesheet">
    <link href="assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" type="text/css">
    @vite([
        'resources/css/landing.css',
    ])
    @show
    <style>
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            padding: 1rem;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .notification.success {
            background-color: #10b981;
            color: #ffffff;
        }
        .notification.error {
            background-color: #ef4444;
            color: #ffffff;
        }
    </style>
</head>
<body class="font-rubik text-base text-slate-900 dark:text-white dark:bg-slate-900">
<!-- Loader Start -->
<!-- <div id="preloader">
    <div id="status">
        <div class="logo">
            <img src="assets/images/logo-icon-64.png" class="d-block mx-auto animate-[spin_10s_linear_infinite]" alt="">
        </div>
        <div class="justify-content-center">
            <div class="text-center">
                <h4 class="mb-0 mt-2 text-lg font-semibold">Upwind</h4>
            </div>
        </div>
    </div>
</div> -->
<!-- Loader End -->

@yield('content')



<!-- JavaScript -->
@vite([
    'resources/js/landing/gumshoe.polyfills.min.js',
    'resources/js/landing/tobii.min.js',
    'resources/js/landing/tiny-slider.js',
    'resources/js/landing/plugins.init.js',
    'resources/js/landing/app.js'
])

@yield('scripts')
</body>
</html>
