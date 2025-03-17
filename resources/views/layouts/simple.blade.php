<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout-mode="detached"  data-topbar-color="dark" data-menu-color="light" data-sidenav-user="true" data-sidenav-size="0px">

<head>
 <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @stack('css')

    @stack('head.end')
</head>

<body class="login-page bg-body-secondary">

@yield('content')

@stack('footer-scripts')
</body>

</html>
