@php($isAdminPwa = request()->is('admin', 'admin/*'))
<link rel="manifest" href="{{ route($isAdminPwa ? 'pwa.admin-manifest' : 'pwa.manifest', [], false) }}" crossorigin="use-credentials" data-turbo-track="reload">
<link rel="apple-touch-icon" href="{{ asset('pwa/icon-192.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#be123c">
<meta name="apple-mobile-web-app-title" content="{{ ($siteSettings->name ?: config('app.name', 'Consultation')).($isAdminPwa ? ' Admin' : '') }}">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="stylesheet" href="{{ asset('css/pwa.css') }}">
<script src="{{ asset('js/pwa.js') }}" data-turbo-eval="false"></script>
