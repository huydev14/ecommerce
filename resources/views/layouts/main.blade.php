<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- IonIcons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <header class="header">
            @include('partials/header')
        </header>

        <aside class="sidebar">
            @include('partials/sidebar')
        </aside>

        <main class="content">
            <div class="content-header">
                @yield('page-header')
            </div>

            <div class="content-body">
                @yield('content')
            </div>
        </main>

        <footer class="footer tw-text-gray-500">
            @include('partials/footer')
        </footer>

        <div id="fluent-toast-container"
            class="tw-fixed tw-top-6 tw-right-6 tw-z-[9999] tw-flex tw-flex-col tw-gap-3 tw-pointer-events-none"></div>

        @stack('scripts')
</body>
</html>
