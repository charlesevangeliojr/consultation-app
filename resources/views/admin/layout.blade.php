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
    @if(request()->routeIs('admin.dashboard'))<link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">@endif
    <link rel="stylesheet" href="{{ asset('css/admin-consultations.css') }}">
    @if(request()->routeIs('admin.consultations'))<link rel="stylesheet" href="{{ asset('css/responsive-calendar.css') }}">@endif
    <script src="{{ asset('js/admin-consultations.js') }}" defer data-turbo-eval="false"></script>
    <script src="{{ asset('js/mobile-navigation.js') }}" defer></script>
    <script src="{{ asset('js/mobile-account-menu.js') }}" defer data-turbo-eval="false"></script>
    @if (request()->routeIs('admin.accounts*', 'admin.roles.*'))
        <link rel="stylesheet" href="{{ asset('css/user-management.css') }}">
        <script src="{{ asset('js/user-management.js') }}" defer></script>
    @endif
    <link rel="stylesheet" href="{{ asset('css/form-controls.css') }}">
    @include('partials.theme-head')
</head>
<body @class(['admin-workspace has-mobile-navigation min-h-screen bg-slate-950 text-white antialiased', 'user-management' => request()->routeIs('admin.accounts*', 'admin.roles.*')])>
    @include('partials.pwa-ui')
    @php
        $adminGroups = [
            ['label' => 'Overview', 'permission' => 'dashboard', 'items' => [
                ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard'],
                ['label' => 'Consultations', 'icon' => 'calendar', 'route' => 'admin.consultations', 'pattern' => 'admin.consultations'],
            ]],
            ['label' => 'People & access', 'permission' => 'accounts', 'items' => [
                ['label' => 'Accounts', 'icon' => 'users', 'route' => 'admin.accounts', 'pattern' => 'admin.accounts*', 'badge' => $pendingTeacherApprovalCount],
                ['label' => 'Roles & permissions', 'icon' => 'shield', 'route' => 'admin.roles.index', 'pattern' => 'admin.roles.*'],
            ]],
            ['label' => 'Configuration', 'permission' => 'settings', 'items' => [
                ['label' => 'Grades & sections', 'icon' => 'school', 'route' => 'admin.school', 'pattern' => 'admin.school*'],
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
                    'admin.school' => 'Grades & sections', 'admin.settings' => 'Settings', default => $item['label'],
                }, 'icon' => $item['icon'], 'url' => route($item['route']), 'badge' => $item['badge'] ?? 0,
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
                    <div class="admin-page-title"><p>Administration</p><h1>@yield('title', 'Dashboard')</h1></div>
                </div>
                <div class="theme-navbar-actions">
                    <span class="admin-access-label">{{ auth()->user()->isAdmin() ? 'Super Admin' : 'Administrator' }}</span>
                    @include('partials.theme-toggle')
                    @include('partials.mobile-account-menu', ['mobileLogoutUrl' => route('admin.logout'), 'mobileProfileUrl' => route('admin.profile.edit')])
                </div>
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
    @include('partials.mobile-navigation', ['mobileLabel' => 'Admin mobile navigation'])
</body>
</html>
