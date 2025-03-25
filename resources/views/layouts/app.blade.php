<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout-mode="detached" data-topbar-color="dark"
    data-menu-color="light" data-sidenav-user="true" data-sidenav-size="0px">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="default-src *; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' 'unsafe-eval' http://cdnjs.cloudflare.com ">
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'unsafe-inline' 'unsafe-eval';">
    @include('layouts.head')

    @stack('before-css')
    @vite('resources/scss/app.scss')
    @stack('css')
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-mini bg-body-tertiary">

    <div class="app-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')
        <main class="app-main">
            <div class="app-content">
                @include('layouts.messages')
                @yield('content')
            </div>
        </main>
        @include('layouts.footer')
    </div>

    @stack('before-scripts')
    @include('layouts.scripts')
    @stack('scripts')
    
</body>
</html>
