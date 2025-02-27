<meta charset="utf-8"/>
<title>{{ isset($title) ? $title . " | " : config('app.name', 'Laravel') }} </title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta content="{{ config('app.name', 'Laravel') }} Phone System Portal" name="description" />
<meta content="{{ config('app.name', 'Laravel') }}" name="{{ config('app.name', 'Laravel') }}" />

<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">


<!--  If your homepage contains a login form, or a modal with login, then when the session ends (by default, after 2 hours)
    then the csrf token is no longer valid and the user sees a page expired warning after they have filled out their login details.
We can work around this with a simple addition to the head of the main layout template. -->
<meta http-equiv="refresh" content="{{ config('session.lifetime') * 60 }}">

<!-- App favicon -->
<link rel="apple-touch-icon" sizes="180x180" href="/storage/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/storage/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/storage/favicon-16x16.png">
<link rel="manifest" href="/storage/site.webmanifest">
<link rel="mask-icon" href="/storage/safari-pinned-tab.svg" color="#f08439">
<link rel="shortcut icon" href="/storage/favicon.ico">
<meta name="msapplication-TileColor" content="#00aba9">
<meta name="msapplication-config" content="/storage/browserconfig.xml">
<meta name="theme-color" content="#ffffff">
