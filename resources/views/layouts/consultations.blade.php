<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#020617">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="account-activity-url" content="{{ route('account.activity') }}">
    <script src="{{ asset('js/account-activity.js') }}" defer data-turbo-eval="false"></script>
    <title>@yield('title') · {{ $siteSettings->name ?? 'ConSync' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-umd.js" defer></script>
    <link rel="stylesheet" href="{{ asset('css/mobile-navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-calendar.css') }}">
    <script src="{{ asset('js/mobile-navigation.js') }}" defer></script>
    <script src="{{ asset('js/mobile-account-menu.js') }}" defer data-turbo-eval="false"></script>
    <script src="{{ asset('js/calendar-schedule-modal.js') }}" defer data-turbo-eval="false"></script>
    @if(auth()->user()->isTeacher() || auth()->user()->isStudent())
        <link rel="stylesheet" href="{{ asset('css/teacher-calendar.css') }}">
        <script src="{{ asset('js/teacher-account-menu.js') }}" defer data-turbo-eval="false"></script>
        <script src="{{ asset('js/profile-photo-cropper.js') }}" defer data-turbo-eval="false"></script>
    @endif
    @if(auth()->user()->isStudent())
        <link rel="stylesheet" href="{{ asset('css/student-portal.css') }}">
        <script src="{{ asset('js/student-profile.js') }}" defer data-turbo-eval="false"></script>
        <script src="{{ asset('js/student-booking.js') }}" defer data-turbo-eval="false"></script>
        <script src="{{ asset('js/address-suggestions.js') }}" defer data-turbo-eval="false"></script>
    @endif
    @if(request()->routeIs('consultations.teacher-profile.*', 'consultations.student-profile.*'))
        <link rel="stylesheet" href="{{ asset('css/teacher-profile.css') }}">
    @endif
    @if(auth()->user()->isTeacher())
        <script src="{{ asset('js/teacher-profile.js') }}" defer data-turbo-eval="false"></script>
        <script src="{{ asset('js/address-suggestions.js') }}" defer data-turbo-eval="false"></script>
    @endif
    <link rel="stylesheet" href="{{ asset('css/form-controls.css') }}">
    @include('partials.theme-head')
</head>
<body class="has-mobile-navigation min-h-screen bg-slate-950 text-slate-100 antialiased">
    @include('partials.pwa-ui')
    @php
        $mobileItems = [];
        if (auth()->user()->isStudent()) {
            $mobileItems = [
                ['label' => 'Dashboard', 'icon' => 'home', 'url' => route('consultations.dashboard'), 'active' => request()->routeIs('consultations.dashboard')],
                ['label' => 'Calendar', 'icon' => 'calendar', 'url' => route('consultations.browse'), 'active' => request()->routeIs('consultations.browse')],
                ['label' => 'My profile', 'icon' => 'profile', 'url' => route('consultations.student-profile.edit'), 'active' => request()->routeIs('consultations.student-profile.*')],
            ];
        } elseif (auth()->user()->isTeacher()) {
            $calendarUrl = request()->routeIs('consultations.teacher') ? request()->fullUrl() : route('consultations.teacher');
            $mobileItems = [
                ['label' => 'Calendar', 'desktopLabel' => 'My calendar', 'icon' => 'calendar', 'url' => $calendarUrl, 'active' => request()->routeIs('consultations.teacher')],
                ['label' => 'Requests', 'desktopLabel' => 'Consultation requests', 'icon' => 'users', 'url' => route('consultations.teacher.requests'), 'active' => request()->routeIs('consultations.teacher.requests')],
                ['label' => 'Availability', 'icon' => 'plus', 'url' => route('consultations.teacher.availability'), 'active' => request()->routeIs('consultations.teacher.availability')],
                ['label' => 'Settings', 'desktopLabel' => 'Consultation settings', 'icon' => 'settings', 'url' => route('consultations.teacher.preferences'), 'active' => request()->routeIs('consultations.teacher.preferences')],
                ['label' => 'My profile', 'icon' => 'profile', 'url' => route('consultations.teacher-profile.edit'), 'active' => request()->routeIs('consultations.teacher-profile.*')],
            ];
        }
    @endphp
    @if (auth()->user()->isStudent())
        @include('student.partials.sidebar', ['items' => $mobileItems])
        <div class="teacher-layout student-layout">
            <header class="mobile-topbar teacher-topbar border-b border-slate-800 bg-slate-900/80">
                <div class="flex min-w-0 items-center justify-between gap-3 px-3 py-3 sm:px-5 lg:px-6">
                    <a href="{{ route('consultations.home') }}" class="min-w-0 flex-1 truncate text-lg font-bold lg:hidden">{{ $siteSettings->name ?: config('app.name') }}<span class="text-rose-400">.</span></a>
                    <div class="hidden lg:block"><p class="text-xs text-slate-500">Student workspace</p><p class="mt-1 text-sm font-medium text-slate-200">{{ collect($mobileItems)->firstWhere('active', true)['label'] ?? 'Student portal' }}</p></div>
                    <div class="theme-navbar-actions">
                        @include('partials.theme-toggle')
                        <a href="{{ route('consultations.student-profile.edit') }}" aria-label="Edit your student profile" class="hidden min-w-0 items-center gap-2 text-sm text-slate-400 lg:flex lg:max-w-xs">
                            @include('student.partials.avatar', ['avatarSize' => 'h-9 w-9'])<span class="truncate">{{ auth()->user()->name }}</span>
                        </a>
                        @include('partials.mobile-account-menu')
                    </div>
                </div>
            </header>
            <main class="student-portal min-w-0 px-3 py-6 sm:px-5 sm:py-8 lg:px-6" id="student-workspace">
                @if (session('status'))
                    <div role="status" class="mb-6 rounded-xl border border-emerald-800 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    @elseif(auth()->user()->isTeacher())
        @include('teacher.partials.sidebar', ['items' => $mobileItems])
        <div class="teacher-layout">
            <header class="mobile-topbar teacher-topbar border-b border-slate-800 bg-slate-900/80">
                <div class="flex min-w-0 flex-wrap items-center justify-between gap-3 px-3 py-3 sm:px-5 lg:px-6">
                    <a href="{{ route('consultations.home') }}" class="min-w-0 flex-1 truncate text-lg font-bold lg:hidden">{{ $siteSettings->name ?: 'ConSync' }}<span class="text-rose-400">.</span></a>
                    <div class="hidden lg:block"><p class="text-xs text-slate-500">Teacher workspace</p><p class="mt-1 text-sm font-medium text-slate-200">{{ collect($mobileItems)->firstWhere('active', true)['desktopLabel'] ?? collect($mobileItems)->firstWhere('active', true)['label'] ?? 'Teacher portal' }}</p></div>
                    <div class="theme-navbar-actions">
                        @include('partials.theme-toggle')
                        <div class="hidden min-w-0 items-center gap-3 lg:flex">
                            <a href="{{ route('consultations.teacher-profile.edit') }}" aria-label="Edit your teacher profile" class="flex min-w-0 items-center gap-2 text-sm text-slate-400 sm:max-w-xs">
                                @include('teacher.partials.avatar', ['avatarSize' => 'h-9 w-9'])
                                <span class="min-w-0 truncate">{{ auth()->user()->name }}</span>
                            </a>
                        </div>
                        @include('partials.mobile-account-menu')
                    </div>
                </div>
            </header>
            <main class="teacher-portal min-w-0 px-3 py-6 sm:px-5 sm:py-8 lg:px-6" id="teacher-workspace">
                @if (session('status'))
                    <div role="status" class="mb-6 rounded-xl border border-emerald-800 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    @else
        <header class="border-b border-slate-800 bg-slate-900/80">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4">
                <a href="{{ route('consultations.home') }}" class="min-w-0 break-words text-lg font-bold [overflow-wrap:anywhere]">{{ $siteSettings->name ?? 'ConSync' }}<span class="text-rose-400">.</span></a>
                <div class="flex w-full min-w-0 flex-wrap items-center gap-x-4 gap-y-2 sm:w-auto">
                    <span class="w-full min-w-0 break-words text-sm text-slate-400 [overflow-wrap:anywhere] sm:w-auto sm:max-w-xs">{{ auth()->user()->name }}</span>
                    @if (auth()->user()->isTeacher())
                        <a href="{{ route('consultations.teacher') }}" class="inline-flex min-h-11 items-center text-sm text-slate-300 hover:text-white">Teacher calendar</a>
                    @endif
                    <form method="POST" action="{{ route('consultations.logout') }}">@csrf<button class="min-h-11 text-sm text-slate-400 hover:text-white">Sign out</button></form>
                </div>
            </div>
        </header>
        <main @class(['mx-auto min-w-0 max-w-6xl px-3 py-6 sm:px-5 sm:py-12', 'teacher-portal' => request()->routeIs('consultations.teacher')])>
            @if (session('status'))
                <div role="status" class="mb-6 rounded-xl border border-emerald-800 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    @endif
    @include('partials.mobile-navigation', ['mobileLabel' => auth()->user()->isStudent() ? 'Student mobile navigation' : 'Teacher mobile navigation'])
</body>
</html>
