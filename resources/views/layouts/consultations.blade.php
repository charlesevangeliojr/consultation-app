<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#020617">
    <title>@yield('title') · {{ $siteSettings->name ?? 'ConSync' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-umd.js" defer></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    @if (auth()->user()->isStudent())
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 flex-col border-r border-slate-800 bg-slate-900 lg:flex">
            <a href="{{ route('consultations.home') }}" class="flex items-center gap-3 border-b border-slate-800 px-6 py-5 text-lg font-bold">
                @if ($siteSettings->logo_path)
                    <img src="{{ asset('storage/'.$siteSettings->logo_path) }}" alt="" class="h-10 w-10 rounded-lg object-contain">
                @else
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-500 text-white">{{ mb_substr($siteSettings->name ?: 'C', 0, 1) }}</span>
                @endif
                <span>{{ $siteSettings->name ?: 'ConSync' }}<span class="text-rose-400">.</span></span>
            </a>
            <nav aria-label="Student navigation" class="flex-1 space-y-2 px-4 py-6">
                <a href="{{ route('consultations.dashboard') }}" class="block rounded-lg px-4 py-3 text-sm font-medium {{ request()->routeIs('consultations.dashboard') ? 'bg-rose-500/10 text-rose-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Dashboard</a>
                <a href="{{ route('consultations.browse') }}" class="block rounded-lg px-4 py-3 text-sm font-medium {{ request()->routeIs('consultations.browse') ? 'bg-rose-500/10 text-rose-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Calendar</a>
                <a href="{{ route('consultations.student-profile.edit') }}" class="block rounded-lg px-4 py-3 text-sm font-medium {{ request()->routeIs('consultations.student-profile.*') ? 'bg-rose-500/10 text-rose-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">My profile</a>
            </nav>
            <div class="border-t border-slate-800 p-4">
                <p class="truncate px-3 text-sm font-semibold">{{ auth()->user()->name }}</p>
                <p class="truncate px-3 pt-1 text-xs text-slate-500">{{ auth()->user()->email }}</p>
                <form method="POST" action="{{ route('consultations.logout') }}" class="mt-3">
                    @csrf
                    <button class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-400 hover:bg-slate-800 hover:text-white">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-20 border-b border-slate-800 bg-slate-900/95 backdrop-blur">
                <div class="flex items-center justify-between px-5 py-4 lg:justify-end lg:px-8">
                    <a href="{{ route('consultations.home') }}" class="flex items-center gap-2 text-base font-bold lg:hidden">
                        @if ($siteSettings->logo_path)
                            <img src="{{ asset('storage/'.$siteSettings->logo_path) }}" alt="" class="h-8 w-8 rounded object-contain">
                        @else
                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-rose-500 text-sm">{{ mb_substr($siteSettings->name ?: 'C', 0, 1) }}</span>
                        @endif
                        <span>{{ $siteSettings->name ?: 'ConSync' }}</span>
                    </a>
                    <span class="hidden text-sm text-slate-400 lg:block">{{ auth()->user()->name }}</span>
                    <button id="student-mobile-menu-button" type="button" aria-expanded="false" aria-controls="student-mobile-menu" class="grid h-10 w-10 place-items-center rounded-lg border border-slate-700 text-slate-200 hover:bg-slate-800 lg:hidden">
                        <span class="sr-only">Toggle navigation</span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                </div>
                <nav id="student-mobile-menu" aria-label="Student navigation" class="hidden border-t border-slate-800 px-5 py-3 lg:hidden">
                    <a href="{{ route('consultations.dashboard') }}" class="block rounded-lg px-3 py-3 text-sm {{ request()->routeIs('consultations.dashboard') ? 'bg-rose-500/10 text-rose-300' : 'text-slate-300 hover:bg-slate-800' }}">Dashboard</a>
                    <a href="{{ route('consultations.browse') }}" class="block rounded-lg px-3 py-3 text-sm {{ request()->routeIs('consultations.browse') ? 'bg-rose-500/10 text-rose-300' : 'text-slate-300 hover:bg-slate-800' }}">Calendar</a>
                    <a href="{{ route('consultations.student-profile.edit') }}" class="block rounded-lg px-3 py-3 text-sm {{ request()->routeIs('consultations.student-profile.*') ? 'bg-rose-500/10 text-rose-300' : 'text-slate-300 hover:bg-slate-800' }}">My profile</a>
                    <form method="POST" action="{{ route('consultations.logout') }}" class="mt-2 border-t border-slate-800 pt-2">
                        @csrf
                        <button class="w-full rounded-lg px-3 py-3 text-left text-sm text-slate-400 hover:bg-slate-800 hover:text-white">Sign out</button>
                    </form>
                </nav>
            </header>
            <main class="mx-auto max-w-6xl px-5 py-8 sm:py-10 lg:px-8">
                @if (session('status'))
                    <div role="status" class="mb-6 rounded-xl border border-emerald-800 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
        <script>
            (() => {
                const button = document.getElementById('student-mobile-menu-button');
                const menu = document.getElementById('student-mobile-menu');
                button?.addEventListener('click', () => {
                    const expanded = button.getAttribute('aria-expanded') === 'true';
                    button.setAttribute('aria-expanded', String(!expanded));
                    menu?.classList.toggle('hidden', expanded);
                });
            })();
        </script>
    @else
        <header class="border-b border-slate-800 bg-slate-900/80">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4">
                <a href="{{ route('consultations.home') }}" class="text-lg font-bold">{{ $siteSettings->name ?? 'ConSync' }}<span class="text-rose-400">.</span></a>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-400">{{ auth()->user()->name }}</span>
                    @if (auth()->user()->isTeacher())
                        <a href="{{ route('consultations.teacher') }}" class="text-sm text-slate-300 hover:text-white">Teacher calendar</a>
                    @endif
                    <form method="POST" action="{{ route('consultations.logout') }}">@csrf<button class="text-sm text-slate-400 hover:text-white">Sign out</button></form>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-5 py-9 sm:py-12">
            @if (session('status'))
                <div role="status" class="mb-6 rounded-xl border border-emerald-800 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    @endif
</body>
</html>
