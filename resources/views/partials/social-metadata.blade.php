@php
    $socialName = $siteSettings->name ?: 'ConSync';
    $socialTitle = $socialName.' — School consultations';
    $socialDescription = 'A single-school consultation app where students request time with teachers, teachers manage hours, and admins manage accounts, approvals, and school setup.';
    $socialUrl = url('/');
    $socialImage = url(\App\Support\PwaIcons::url(512, $siteSettings->logo_path));
@endphp
<meta name="description" content="{{ $socialDescription }}">
<link rel="canonical" href="{{ $socialUrl }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $socialName }}">
<meta property="og:title" content="{{ $socialTitle }}">
<meta property="og:description" content="{{ $socialDescription }}">
<meta property="og:url" content="{{ $socialUrl }}">
<meta property="og:image" content="{{ $socialImage }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="512">
<meta property="og:image:height" content="512">
<meta property="og:image:alt" content="{{ $socialName }} logo">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $socialTitle }}">
<meta name="twitter:description" content="{{ $socialDescription }}">
<meta name="twitter:image" content="{{ $socialImage }}">
<meta name="twitter:image:alt" content="{{ $socialName }} logo">
