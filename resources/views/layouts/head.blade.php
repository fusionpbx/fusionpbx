<title>{{ $title ?? config('app.name', 'Backoffice Dashboard') }} | {{ config('app.name', 'Admin Panel') }}</title>
<meta name="description" content="{{ $description ?? 'Secure administrative panel for managing application resources and settings.' }}">

<meta name="author" content="{{ $author ?? config('app.author', 'Your Company Name') }}">
<meta name="keywords" content="{{ $keywords ?? 'admin, dashboard, backoffice, management, panel' }}">

<meta http-equiv="Content-Security-Policy" content="{{ $csp ?? "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;" }}">
<meta http-equiv="X-Content-Type-Options" content="nosniff">
<meta http-equiv="Referrer-Policy" content="same-origin">
