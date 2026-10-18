<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="account-activity-url" content="{{ route('account.activity') }}">
    <script src="{{ asset('js/account-activity.js') }}" defer data-turbo-eval="false"></script>
    <title>@yield('title', 'Admin') · {{ $siteSettings->name }}</title>
    <link rel="icon" href="{{ $siteSettings->favicon_path ? asset('storage/'.$siteSettings->favicon_path) : asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-umd.js" defer></script>
    <link rel="stylesheet" href="{{ asset('css/mobile-navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-navigation.css') }}">
    <script src="{{ asset('js/admin-navigation.js') }}" defer data-turbo-eval="false"></script>
    <script src="{{ asset('js/mobile-navigation.js') }}" defer></script>
    <script src="{{ asset('js/mobile-account-menu.js') }}" defer data-turbo-eval="false"></script>
    @if (request()->routeIs('admin.accounts*', 'admin.roles.*'))
        <link rel="stylesheet" href="{{ asset('css/user-management.css') }}">
        <script src="{{ asset('js/user-management.js') }}" defer></script>
    @endif
    <link rel="stylesheet" href="{{ asset('css/form-controls.css') }}">
</head>
<body @class(['admin-workspace has-mobile-navigation min-h-screen bg-slate-950 text-white antialiased', 'user-management' => request()->routeIs('admin.accounts*', 'admin.roles.*')])>
    @php
        $adminGroups = [
            ['label' => 'Overview', 'permission' => 'dashboard', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard'],
                ['label' => 'Consultations', 'icon' => 'calendar', 'route' => 'admin.consultations', 'pattern' => 'admin.consultations'],
            ]],
            ['label' => 'People & access', 'permission' => 'accounts', 'items' => [
                ['label' => 'Accounts', 'icon' => 'users', 'route' => 'admin.accounts', 'pattern' => 'admin.accounts*'],
                ['label' => 'Roles & permissions', 'icon' => 'shield', 'route' => 'admin.roles.index', 'pattern' => 'admin.roles.*'],
            ]],
            ['label' => 'School setup', 'permission' => 'settings', 'items' => [
                ['label' => 'Academic lists', 'icon' => 'school', 'route' => 'admin.school', 'pattern' => 'admin.school*'],
                ['label' => 'School settings', 'icon' => 'settings', 'route' => 'admin.settings', 'pattern' => 'admin.settings'],
            ]],
        ];
        $adminGroups = array_values(array_filter($adminGroups, fn ($group) => auth()->user()->canAccess($group['permission'], 'view')));
        $adminHome = count($adminGroups) ? route($adminGroups[0]['items'][0]['route']) : route('admin.profile.edit');
        $mobileItems = [];
        foreach ($adminGroups as $group) {
            foreach ($group['items'] as $item) {
                if ($item['route'] === 'admin.roles.index') continue;
                $mobileItems[] = ['label' => match ($item['route']) {
                    'admin.school' => 'School', 'admin.settings' => 'Settings', default => $item['label'],
                }, 'icon' => $item['icon'], 'url' => route($item['route']),
                    'active' => request()->routeIs($item['pattern']) || ($item['route'] === 'admin.accounts' && request()->routeIs('admin.roles.*'))];
            }
        }
    @endphp
    <div class="admin-shell">
        <aside class="admin-sidebar" aria-label="Administration sidebar">
            @include('admin.partials.navigation-brand')
            @include('admin.partials.navigation', ['navigationLabel' => 'Admin navigation'])
            <div class="admin-sidebar-footer">@include('admin.partials.account-dropdown')</div>
        </aside>
        <div class="admin-content">
            <header class="mobile-topbar admin-topbar">
                <div class="admin-topbar-heading">
                    <button type="button" class="admin-menu-toggle" data-admin-menu-open aria-label="Open admin menu" aria-controls="admin-mobile-menu" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                    <div class="admin-page-title"><p>Administration</p><h1>@yield('title', 'Dashboard')</h1></div>
                </div>
                <span class="admin-access-label">{{ auth()->user()->isAdmin() ? 'Super Admin' : 'Administrator' }}</span>
                @include('partials.mobile-account-menu', ['mobileLogoutUrl' => route('admin.logout'), 'mobileProfileUrl' => route('admin.profile.edit')])
            </header>
            @if(auth()->user()->canAccess('accounts', 'view') && request()->routeIs('admin.accounts*', 'admin.roles.*'))
                <nav aria-label="People and access navigation" class="admin-section-tabs">
                    <a href="{{ route('admin.accounts') }}" @if(request()->routeIs('admin.accounts*')) aria-current="page" @endif>Accounts</a>
                    <a href="{{ route('admin.roles.index') }}" @if(request()->routeIs('admin.roles.*')) aria-current="page" @endif>Roles &amp; permissions</a>
                </nav>
            @endif
            <main class="admin-main" id="admin-workspace">@yield('content')</main>
        </div>
    </div>
    <dialog id="admin-mobile-menu" class="admin-mobile-menu" aria-labelledby="admin-mobile-menu-title" data-admin-menu>
        <div class="admin-drawer-layout">
            <div class="admin-drawer-header"><h2 id="admin-mobile-menu-title">Admin menu</h2><button type="button" class="admin-menu-close" data-admin-menu-close aria-label="Close admin menu" autofocus><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/></svg></button></div>
            @include('admin.partials.navigation-brand')
            @include('admin.partials.navigation', ['navigationLabel' => 'Admin menu navigation'])
            <div class="admin-drawer-footer">
                <a href="{{ route('admin.profile.edit') }}" class="admin-navigation-link" @if(request()->routeIs('admin.profile.*')) aria-current="page" @endif>@include('partials.navigation-icon', ['icon' => 'profile'])<span>My profile</span></a>
                <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="admin-navigation-link admin-sign-out">@include('partials.navigation-icon', ['icon' => 'logout'])<span>Sign out</span></button></form>
            </div>
        </div>
    </dialog>
    @include('partials.mobile-navigation', ['mobileLabel' => 'Admin mobile navigation'])
</body>
</html>
