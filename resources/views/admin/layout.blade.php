<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Admin') · {{ $siteSettings->name }}</title>
    @if ($siteSettings->favicon_path)
        <link rel="icon" href="{{ asset('storage/'.$siteSettings->favicon_path) }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-umd.js" defer></script>
    <link rel="stylesheet" href="{{ asset('css/mobile-navigation.css') }}">
    <script src="{{ asset('js/mobile-navigation.js') }}" defer></script>
    @if (request()->routeIs('admin.accounts*', 'admin.roles.*'))
        <link rel="stylesheet" href="{{ asset('css/user-management.css') }}">
        <script src="{{ asset('js/user-management.js') }}" defer></script>
    @endif
</head>
    <body @class(['has-mobile-navigation min-h-screen bg-slate-950 text-white antialiased', 'user-management' => request()->routeIs('admin.accounts*', 'admin.roles.*')])>
    <div class="min-h-screen lg:flex">
        <aside class="hidden w-64 shrink-0 flex-col border-r border-slate-800 bg-slate-900 lg:flex lg:min-h-screen">
            <a href="{{ auth()->user()->canAccess('dashboard', 'view') ? route('admin.dashboard') : (auth()->user()->canAccess('settings', 'view') ? route('admin.settings') : route('admin.accounts')) }}" class="flex items-center gap-3 border-b border-slate-800 px-6 py-6 text-lg font-bold">
                @if ($siteSettings->logo_path)
                    <img src="{{ asset('storage/'.$siteSettings->logo_path) }}" alt="" class="h-9 w-9 rounded object-contain">
                @endif
                <span>{{ $siteSettings->name }} <span class="text-rose-400">Admin</span></span>
            </a>
            <nav aria-label="Admin navigation" class="flex gap-2 overflow-x-auto p-4 md:flex-1 md:flex-col">
                @if (auth()->user()->canAccess('settings', 'view'))
                    <a href="{{ route('admin.school') }}" class="shrink-0 rounded-lg px-4 py-3 text-sm {{ request()->routeIs('admin.school*') ? 'bg-rose-500/10 text-rose-300' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">School lists</a>
                @endif
                @if (auth()->user()->canAccess('dashboard', 'view'))
                    <a href="{{ route('admin.consultations') }}" class="shrink-0 rounded-lg px-4 py-3 text-sm {{ request()->routeIs('admin.consultations') ? 'bg-rose-500/10 text-rose-300' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">Consultation monitoring</a>
                @endif
                @if (auth()->user()->canAccess('dashboard', 'view'))
                <a href="{{ route('admin.dashboard') }}" @class([
                    'flex shrink-0 items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                    'bg-rose-500/10 text-rose-300' => request()->routeIs('admin.dashboard'),
                    'text-slate-400 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.dashboard'),
                ])>
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>
                    Dashboard
                </a>
                @endif
                @if (auth()->user()->canAccess('settings', 'view'))
                <a href="{{ route('admin.settings') }}" @class([
                    'flex shrink-0 items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                    'bg-rose-500/10 text-rose-300' => request()->routeIs('admin.settings'),
                    'text-slate-400 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.settings'),
                ])>
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="m19.4 15 .1.1a1.7 1.7 0 1 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a1.7 1.7 0 1 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 1 1-2.4-2.4l.1-.1a1.7 1.7 0 0 0-1.2-2.9H4a1.7 1.7 0 1 1 0-3.4h.2a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a1.7 1.7 0 1 1 2.4-2.4l.1.1a1.7 1.7 0 0 0 2.9-1.2V4a1.7 1.7 0 1 1 3.4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a1.7 1.7 0 1 1 2.4 2.4l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a1.7 1.7 0 1 1 0 3.4h-.2a1.7 1.7 0 0 0-1.2.9Z"/></svg>
                    Settings
                </a>
                @endif
                @if (auth()->user()->canAccess('accounts', 'view'))
                <details class="min-w-0 shrink-0 md:shrink" @if (request()->routeIs('admin.accounts*', 'admin.roles.*')) open @endif>
                    <summary @class([
                        'flex cursor-pointer list-none items-center justify-between gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                        'bg-rose-500/10 text-rose-300' => request()->routeIs('admin.accounts*', 'admin.roles.*'),
                        'text-slate-400 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.accounts*', 'admin.roles.*'),
                    ])>
                        <span class="flex items-center gap-3"><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.8 20v-1.4a6.2 6.2 0 0 1 12.4 0V20M17 5.2a3.5 3.5 0 0 1 0 6.6m1.1 2.4a5.4 5.4 0 0 1 3.1 4.9V20"/></svg> User Management</span>
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="mt-1 flex gap-1 pl-4 md:flex-col">
                        <a href="{{ route('admin.roles.index') }}" @class([
                            'rounded-lg px-4 py-2 text-sm transition',
                            'bg-slate-800 text-white' => request()->routeIs('admin.roles.*'),
                            'text-slate-400 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.roles.*'),
                        ])>Roles</a>
                        <a href="{{ route('admin.accounts') }}" @class([
                            'rounded-lg px-4 py-2 text-sm transition',
                            'bg-slate-800 text-white' => request()->routeIs('admin.accounts*'),
                            'text-slate-400 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.accounts*'),
                        ])>Accounts</a>
                    </div>
                </details>
                @endif
            </nav>
            <div class="hidden border-t border-slate-800 p-4 lg:block">
                @include('admin.partials.account-dropdown')
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="flex items-center justify-between border-b border-slate-800 px-6 py-4 md:px-10">
                <h1 class="text-sm font-medium text-slate-300">@yield('title')</h1>
                <span class="rounded-full border border-slate-700 px-3 py-1 text-xs text-slate-400">Administrator</span>
            </header>
            @if(auth()->user()->canAccess('accounts', 'view') && request()->routeIs('admin.accounts*', 'admin.roles.*'))
                <nav aria-label="User management navigation" class="flex gap-3 border-b border-slate-800 px-6 py-2 lg:hidden">
                    <a href="{{ route('admin.accounts') }}" class="inline-flex min-h-11 items-center px-2 text-sm {{ request()->routeIs('admin.accounts*') ? 'text-rose-300' : 'text-slate-400' }}">Accounts</a>
                    <a href="{{ route('admin.roles.index') }}" class="inline-flex min-h-11 items-center px-2 text-sm {{ request()->routeIs('admin.roles.*') ? 'text-rose-300' : 'text-slate-400' }}">Roles</a>
                </nav>
            @endif
            <main class="mx-auto max-w-6xl px-6 py-10 md:px-10 md:py-12">
                @yield('content')
            </main>
            <div class="border-t border-slate-800 p-4 lg:hidden">
                @include('admin.partials.account-dropdown')
            </div>
        </div>
    </div>
    @php
        $mobileItems = [];
        if (auth()->user()->canAccess('dashboard', 'view')) $mobileItems[] = ['label' => 'Dashboard', 'icon' => 'home', 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')];
        if (auth()->user()->canAccess('accounts', 'view')) $mobileItems[] = ['label' => 'Accounts', 'icon' => 'users', 'url' => route('admin.accounts'), 'active' => request()->routeIs('admin.accounts*', 'admin.roles.*')];
        if (auth()->user()->canAccess('settings', 'view')) $mobileItems[] = ['label' => 'School', 'icon' => 'school', 'url' => route('admin.school'), 'active' => request()->routeIs('admin.school*')];
        if (auth()->user()->canAccess('dashboard', 'view')) $mobileItems[] = ['label' => 'Monitor', 'icon' => 'monitor', 'url' => route('admin.consultations'), 'active' => request()->routeIs('admin.consultations')];
        if (auth()->user()->canAccess('settings', 'view')) $mobileItems[] = ['label' => 'Settings', 'icon' => 'settings', 'url' => route('admin.settings'), 'active' => request()->routeIs('admin.settings')];
    @endphp
    @include('partials.mobile-navigation', ['mobileLabel' => 'Admin mobile navigation'])
</body>
</html>
