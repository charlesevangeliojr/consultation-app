<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ConSync connects students, teachers, and administrators through one organized academic consultation platform.">
    <title>{{ $siteSettings->name ?: 'ConSync' }} — Academic consultations, organized</title>
    @if ($siteSettings->favicon_path)
        <link rel="icon" href="{{ asset('storage/'.$siteSettings->favicon_path) }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-umd.js" defer></script>
    <style>
        :root { color-scheme: dark; }
        body { background: #020617; color: #f8fafc; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .hero-glow { background: radial-gradient(ellipse at 70% 12%, rgba(244,63,94,.16), transparent 42%); }
        .grid-glow { background-image: linear-gradient(rgba(148,163,184,.035) 1px, transparent 1px), linear-gradient(90deg, rgba(148,163,184,.035) 1px, transparent 1px); background-size: 48px 48px; mask-image: linear-gradient(to bottom, black, transparent 86%); }
        .soft-shadow { box-shadow: 0 30px 100px rgba(0,0,0,.35); }
        .focus-rose:focus-visible { outline: 2px solid #fb7185; outline-offset: 4px; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; } }
    </style>
    @include('partials.theme-head')
</head>
<body class="antialiased selection:bg-rose-500/30 selection:text-white">
    @include('partials.pwa-ui')
    @php
        $landingWeekStart = now()->startOfWeek();
        $landingAvailabilityDates = $publicAvailability->pluck('date')->map(fn ($date) => $date->toDateString())->unique()->all();
    @endphp
    <a href="#main" class="focus-rose sr-only z-50 rounded-lg bg-slate-900 px-4 py-3 text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/90 backdrop-blur-xl">
        <nav aria-label="Main navigation" class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-5 py-4 sm:px-8">
            <a href="#home" class="focus-rose flex min-w-0 items-center gap-3 rounded-lg" aria-label="{{ $siteSettings->name ?: 'ConSync' }} home">
                @if ($siteSettings->logo_path)
                    <img src="{{ asset('storage/'.$siteSettings->logo_path) }}" alt="" class="h-10 w-10 rounded-lg object-contain">
                @else
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-500 text-white shadow-lg shadow-rose-950/40">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="3"/><path stroke-linecap="round" d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17M8 13h2m4 0h2m-8 3h2m4 0h2"/><path stroke-linecap="round" stroke-linejoin="round" d="m15 13 1.4 1.4L19 12"/></svg>
                    </span>
                @endif
                <span class="truncate text-lg font-bold tracking-tight">{{ $siteSettings->name ?: 'ConSync' }}<span class="text-rose-400">.</span></span>
            </a>

            <div class="hidden items-center gap-4 lg:flex xl:gap-7">
                <a class="focus-rose text-sm text-slate-300 transition hover:text-white" href="#home">Home</a>
                <a class="focus-rose text-sm text-slate-300 transition hover:text-white" href="#features">Features</a>
                <a class="focus-rose text-sm text-slate-300 transition hover:text-white" href="#how-it-works">How it works</a>
                <a class="focus-rose text-sm text-slate-300 transition hover:text-white" href="#calendar">Faculty calendar</a>
                <a class="focus-rose text-sm text-slate-300 transition hover:text-white" href="#about">About</a>
                <a href="{{ route('register') }}" class="focus-rose rounded-lg bg-rose-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-400">Register</a>
                <a href="{{ route('portal.login') }}" class="focus-rose rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Log in</a>
            </div>

            <div class="theme-navbar-actions">
            @include('partials.theme-toggle')
            <button type="button" id="mobile-menu-button" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open navigation menu" class="focus-rose grid h-10 w-10 place-items-center rounded-lg border border-slate-700 text-slate-200 hover:bg-slate-800 lg:hidden">
                <svg id="menu-open-icon" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg id="menu-close-icon" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
            </div>
        </nav>
        <div id="mobile-menu" class="hidden border-t border-slate-800 bg-slate-950 px-5 py-4 lg:hidden">
            <div class="mx-auto grid max-w-7xl gap-1">
                <a class="rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-slate-900 hover:text-white" href="#home">Home</a>
                <a class="rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-slate-900 hover:text-white" href="#features">Features</a>
                <a class="rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-slate-900 hover:text-white" href="#how-it-works">How it works</a>
                <a class="rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-slate-900 hover:text-white" href="#calendar">Faculty calendar</a>
                <a class="rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-slate-900 hover:text-white" href="#about">About</a>
                <a class="rounded-lg px-3 py-2.5 text-sm font-semibold text-rose-300 hover:bg-slate-900 hover:text-rose-200" href="{{ route('register') }}">Register</a>
                <a class="mt-2 rounded-lg bg-rose-500 px-3 py-3 text-center text-sm font-semibold text-white hover:bg-rose-400" href="{{ route('portal.login') }}">Log in</a>
            </div>
        </div>
    </header>

    <main id="main">
        <section id="home" class="hero-glow relative isolate overflow-hidden">
            <div class="grid-glow pointer-events-none absolute inset-0 -z-10"></div>
            <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 pb-24 pt-16 sm:px-8 sm:pb-32 sm:pt-24 lg:grid-cols-[1.02fr_.98fr] lg:gap-10 lg:pt-28">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-rose-400/20 bg-rose-400/5 px-3 py-1.5 text-xs font-medium text-rose-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span> Academic consultations, in sync
                    </div>
                    <h1 class="mt-7 max-w-2xl text-4xl font-bold leading-[1.08] tracking-tight text-slate-50 sm:text-6xl lg:text-[4.25rem]">Your academic consultations, <span class="text-rose-400">perfectly organized.</span></h1>
                    <p class="mt-6 max-w-xl text-base leading-7 text-slate-400 sm:text-lg sm:leading-8">Book consultations, connect with faculty, manage schedules, and stay updated with one centralized academic calendar.</p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('portal.login') }}" class="focus-rose inline-flex items-center justify-center gap-2 rounded-xl bg-rose-500 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-rose-950/30 transition hover:-translate-y-0.5 hover:bg-rose-400">Book a consultation <span aria-hidden="true">→</span></a>
                        <a href="{{ route('register') }}" class="focus-rose inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-900/60 px-6 py-3.5 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-900">Register</a>
                    </div>
                    <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-2"><svg class="h-4 w-4 text-rose-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.25a1 1 0 0 1-1.42 0L3.29 9.155A1 1 0 0 1 4.71 7.74l4.09 4.1 6.49-6.54a1 1 0 0 1 1.414-.01Z" clip-rule="evenodd"/></svg> One shared schedule</span>
                        <span class="inline-flex items-center gap-2"><svg class="h-4 w-4 text-rose-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.25a1 1 0 0 1-1.42 0L3.29 9.155A1 1 0 0 1 4.71 7.74l4.09 4.1 6.49-6.54a1 1 0 0 1 1.414-.01Z" clip-rule="evenodd"/></svg> Built for school communities</span>
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-[600px]">
                    <div class="absolute -inset-8 -z-10 rounded-full bg-rose-500/10 blur-3xl"></div>
                    <div class="soft-shadow overflow-hidden rounded-2xl border border-slate-700/80 bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-800 px-5 py-4">
                            <div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-lg bg-rose-500/10 text-rose-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="3"/><path stroke-linecap="round" d="M7.5 3.5v3m9-3v3m-13 3h17"/></svg></span><div><p class="text-sm font-semibold text-slate-100">Faculty calendar</p><p class="text-xs text-slate-500">A clear view of what's ahead</p></div></div>
                            <span class="rounded-full border border-slate-700 bg-slate-950 px-2.5 py-1 text-[10px] font-medium uppercase tracking-wider text-slate-400">Public schedule</span>
                        </div>
                        <div class="grid gap-0 md:grid-cols-[1.1fr_.9fr]">
                            <div class="p-5 sm:p-6">
                                <div class="flex items-center justify-between"><div><p class="text-xs text-slate-500">{{ now()->format('F Y') }}</p><p class="mt-1 text-lg font-semibold">This week</p></div><a href="{{ route('portal.login') }}" class="text-xs font-medium text-rose-300 hover:text-rose-200">Open calendar →</a></div>
                                <div class="mt-5 grid grid-cols-7 gap-1 text-center">
                                    @foreach (range(0, 6) as $dayOffset)
                                        @php $dayDate = $landingWeekStart->copy()->addDays($dayOffset); $hasAvailability = in_array($dayDate->toDateString(), $landingAvailabilityDates, true); @endphp
                                        <div class="relative rounded-lg py-2 {{ $dayDate->isToday() ? 'bg-rose-500 text-white shadow-lg shadow-rose-950/30' : 'text-slate-500' }}"><span class="block text-[10px]">{{ $dayDate->format('D') }}</span><span class="mt-1 block text-xs font-semibold">{{ $dayDate->format('j') }}</span>@if($hasAvailability)<span class="mx-auto mt-1 block h-1 w-1 rounded-full {{ $dayDate->isToday() ? 'bg-white' : 'bg-rose-400' }}"></span>@endif</div>
                                    @endforeach
                                </div>
                                <div class="mt-5 space-y-2.5">
                                    @forelse($publicAvailability->take(2) as $period)
                                        <div class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3"><span class="h-8 w-1 rounded-full bg-rose-400"></span><div class="min-w-0 flex-1"><p class="truncate text-xs font-medium">{{ $period->teacher->name }} · Consultation hours</p><p class="mt-1 text-[10px] text-slate-500">{{ $period->date->format('M j') }} · {{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($period->ends_at)->format('g:i A') }}@if($period->location) · {{ $period->location }}@endif</p></div><span class="h-2 w-2 rounded-full bg-emerald-400"></span></div>
                                    @empty
                                        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4 text-xs leading-5 text-slate-400">Teachers’ published consultation times will appear here. Sign in to browse the faculty calendar.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="border-t border-slate-800 bg-slate-950/45 p-5 sm:p-6 md:border-l md:border-t-0">
                                <div class="flex items-center justify-between"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Published faculty hours</p><span class="text-[10px] text-slate-600">Public only</span></div>
                                <div class="mt-5 space-y-4">
                                    @forelse($publicAvailability->take(3) as $period)
                                        <div class="flex gap-3"><div class="w-12 shrink-0 pt-0.5 text-[10px] font-medium text-slate-500">{{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i') }}</div><div class="min-w-0 flex-1 rounded-lg border border-rose-400/20 bg-rose-400/5 p-3"><p class="text-xs font-semibold text-slate-100">{{ $period->teacher->name }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $period->date->format('D, M j') }} · {{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($period->ends_at)->format('g:i A') }}</p>@if($period->location)<p class="mt-1 text-[10px] text-slate-500">{{ $period->location }}</p>@endif<span class="mt-2 inline-flex rounded-full bg-emerald-400/10 px-2 py-0.5 text-[9px] font-medium text-emerald-200">Open hours</span></div></div>
                                    @empty
                                        <div class="rounded-lg border border-slate-800 bg-slate-900 p-4 text-xs leading-5 text-slate-400">No upcoming hours have been published yet.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-800 px-5 py-3 text-[10px] text-slate-500"><span>Published availability only · No student appointment details</span><span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Updated from schedules</span></div>
                    </div>
                    <div class="absolute -bottom-5 -left-4 hidden items-center gap-3 rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 shadow-2xl sm:flex"><span class="grid h-9 w-9 place-items-center rounded-lg bg-emerald-400/10 text-emerald-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg></span><div><p class="text-xs font-semibold">One connected schedule</p><p class="mt-1 text-[10px] text-slate-500">Students · Faculty · Admin</p></div></div>
                </div>
            </div>
        </section>

        <section id="features" class="border-t border-slate-800 bg-slate-950/55 py-24 sm:py-28">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="mx-auto max-w-2xl text-center"><p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">One platform, in sync</p><h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Everything consultations need.</h2><p class="mt-4 leading-7 text-slate-400">A calmer way to coordinate academic support—from finding a time to keeping everyone informed.</p></div>
                <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $features = [
                            ['calendar', 'Smart appointment booking', 'Find faculty, explore published availability, and request a time that works for everyone.'],
                            ['grid', 'Interactive calendar', 'Keep consultation schedules organized in clear daily, weekly, and monthly views.'],
                            ['status', 'Faculty availability', 'Share simple availability signals so students know when and where to connect.'],
                            ['bell', 'Timely notifications', 'Keep students and teachers informed about requests, updates, and upcoming meetings.'],
                            ['device', 'Made for every screen', 'Use ConSync on a phone, tablet, laptop, or installable home-screen experience.'],
                            ['tv', 'Faculty TV display', 'Bring approved availability and public consultation periods to screens outside offices.'],
                        ];
                    @endphp
                    @foreach ($features as [$icon, $title, $description])
                        <article class="group rounded-2xl border border-slate-800 bg-slate-900 p-6 transition duration-200 hover:-translate-y-1 hover:border-rose-400/40 hover:bg-slate-900/90">
                            <span class="grid h-11 w-11 place-items-center rounded-xl border border-rose-400/15 bg-rose-400/5 text-rose-300">
                                @if ($icon === 'calendar')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="3"/><path stroke-linecap="round" d="M7.5 3.5v3m9-3v3m-13 3h17m-12 4h.01m4 0h.01m-4 3h.01"/></svg>
                                @elseif ($icon === 'grid')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>
                                @elseif ($icon === 'status')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                                @elseif ($icon === 'bell')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4"/></svg>
                                @elseif ($icon === 'device')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path stroke-linecap="round" d="M10 18h4"/></svg>
                                @else<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="2.5" y="4.5" width="19" height="13" rx="2"/><path stroke-linecap="round" d="M8 21h8m-4-3.5V21"/><path d="M6 8h12v6H6z"/></svg>@endif
                            </span>
                            <h3 class="mt-5 text-base font-semibold text-slate-100">{{ $title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-400">{{ $description }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="how-it-works" class="py-24 sm:py-28">
            <div class="mx-auto grid max-w-7xl gap-14 px-5 sm:px-8 lg:grid-cols-[.8fr_1.2fr] lg:gap-24">
                <div class="lg:sticky lg:top-32 lg:self-start"><p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">A simple process</p><h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">From “let’s meet” to on the calendar.</h2><p class="mt-5 leading-7 text-slate-400">A clear, guided flow gives students a simple way to ask for help and gives faculty the context to plan ahead.</p><a href="{{ route('portal.login') }}" class="focus-rose mt-7 inline-flex items-center gap-2 text-sm font-semibold text-rose-300 hover:text-rose-200">Get started <span aria-hidden="true">→</span></a></div>
                <ol class="relative space-y-0 border-l border-slate-800 pl-8">
                    @foreach ([['01', 'Find your faculty', 'Sign in with an authorized school account, then browse a teacher or department calendar.'], ['02', 'Choose a time', 'Pick an open consultation period and share a brief topic so the teacher can prepare.'], ['03', 'Get a decision', 'The schedule is checked for conflicts. The request enters the teacher’s queue for confirmation or rejection.'], ['04', 'See it on the calendar', 'The requested time appears in the teacher calendar with its pending or confirmed status.'], ['05', 'Close the loop', 'After the consultation, faculty can mark the visit complete and keep the schedule current.']] as [$number, $title, $description])
                        <li class="relative pb-10 last:pb-0"><span class="absolute -left-[2.55rem] top-0 grid h-8 w-8 place-items-center rounded-full border border-rose-400/30 bg-slate-950 text-[10px] font-bold text-rose-300">{{ $number }}</span><h3 class="text-base font-semibold">{{ $title }}</h3><p class="mt-2 max-w-xl text-sm leading-6 text-slate-400">{{ $description }}</p></li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section id="calendar" class="border-y border-slate-800 bg-slate-950/55 py-24 sm:py-28">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 sm:px-8 lg:grid-cols-2 lg:gap-20">
                <div class="order-2 rounded-2xl border border-slate-700 bg-slate-900 p-5 soft-shadow sm:p-7 lg:order-1">
                    @php $monthFirstDay = now()->startOfMonth(); $monthDayCount = now()->daysInMonth; $monthOffset = $monthFirstDay->dayOfWeekIso - 1; @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs text-slate-500">Public faculty calendar</p><h3 class="mt-1 text-lg font-semibold">Consultation availability</h3></div><span class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-300">{{ now()->format('F Y') }}</span></div>
                    <div class="mt-6 grid grid-cols-7 gap-1 text-center text-[10px] text-slate-500">@foreach (['M','T','W','T','F','S','S'] as $day)<span class="py-2 font-medium">{{ $day }}</span>@endforeach</div>
                    <div class="grid grid-cols-7 gap-1 text-center text-xs">@foreach (range(0, 41) as $cell) @php $dayNumber = $cell - $monthOffset + 1; $inMonth = $dayNumber > 0 && $dayNumber <= $monthDayCount; $hasAvailability = $inMonth && in_array($monthFirstDay->copy()->addDays($dayNumber - 1)->toDateString(), $landingAvailabilityDates, true); $isToday = $inMonth && $monthFirstDay->copy()->addDays($dayNumber - 1)->isToday(); @endphp <div class="relative grid aspect-square place-items-center rounded-lg {{ $isToday ? 'bg-rose-500 font-semibold text-white' : ($inMonth ? 'text-slate-300' : 'text-slate-800') }}">{{ $inMonth ? $dayNumber : '' }}@if ($hasAvailability)<span class="absolute bottom-1 h-1 w-1 rounded-full {{ $isToday ? 'bg-white' : 'bg-rose-400' }}"></span>@endif</div> @endforeach</div>
                    <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-800 pt-4 text-[10px] text-slate-400"><span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-rose-400"></span>Open slots</span><span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-600"></span>Unavailable</span><span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-rose-500 ring-2 ring-rose-300/30"></span>Selected</span></div>
                    <p class="mt-4 text-[10px] leading-5 text-slate-500">Dots indicate published consultation dates. Student bookings and appointment details require sign-in.</p>
                </div>
                <div class="order-1 lg:order-2"><p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">One calendar, less confusion</p><h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">See availability before you make the trip.</h2><p class="mt-5 leading-7 text-slate-400">Browse published consultation periods, find the right faculty member, and understand where to go. Personal bookings and student details stay behind sign-in.</p><div class="mt-8 space-y-4"><div class="flex gap-3"><span class="mt-1 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-rose-400/10 text-rose-300"><svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="m16.7 5.3.01 1.4-7.2 7.3a1 1 0 0 1-1.42 0L3.3 9.15a1 1 0 0 1 1.42-1.41l4.09 4.1 6.49-6.54a1 1 0 0 1 1.41 0Z" clip-rule="evenodd"/></svg></span><p class="text-sm text-slate-300">Find open periods by faculty or department</p></div><div class="flex gap-3"><span class="mt-1 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-rose-400/10 text-rose-300"><svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="m16.7 5.3.01 1.4-7.2 7.3a1 1 0 0 1-1.42 0L3.3 9.15a1 1 0 0 1 1.42-1.41l4.09 4.1 6.49-6.54a1 1 0 0 1 1.41 0Z" clip-rule="evenodd"/></svg></span><p class="text-sm text-slate-300">Choose a consultation location with confidence</p></div><div class="flex gap-3"><span class="mt-1 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-rose-400/10 text-rose-300"><svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="m16.7 5.3.01 1.4-7.2 7.3a1 1 0 0 1-1.42 0L3.3 9.15a1 1 0 0 1 1.42-1.41l4.09 4.1 6.49-6.54a1 1 0 0 1 1.41 0Z" clip-rule="evenodd"/></svg></span><p class="text-sm text-slate-300">Keep private appointment information protected</p></div></div></div>
            </div>
        </section>

        <section id="tv-display" class="py-24 sm:py-28">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="mx-auto max-w-2xl text-center"><p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">Beyond the browser</p><h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">A helpful glance outside the faculty room.</h2><p class="mt-4 leading-7 text-slate-400">A dedicated landscape display can make approved faculty availability visible where students need it—without exposing private appointment details.</p></div>
                <div class="mt-12 rounded-[1.75rem] border border-slate-700 bg-slate-900 p-3 soft-shadow sm:p-5">
                    <div class="overflow-hidden rounded-2xl border border-slate-700 bg-[#080f20]">
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 px-5 py-4 sm:px-8"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-500 text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="3"/><path stroke-linecap="round" d="M7.5 3.5v3m9-3v3m-13 3h17"/></svg></span><div><p class="text-sm font-semibold">Faculty consultation desk</p><p class="mt-1 text-xs text-slate-500">Published faculty availability</p></div></div><div class="text-right"><p class="text-xs font-medium text-slate-300">{{ now()->format('l · F j, Y') }}</p><p class="mt-1 text-[10px] text-slate-500">TV layout showcase</p></div></div>
                        <div class="grid gap-5 p-5 sm:p-8 lg:grid-cols-[1fr_260px]">
                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @forelse ($publicTeachers as $teacher)
                                    @php $nextPublicTime = $teacher->publicAvailability->first(); @endphp
                                    <article class="rounded-xl border border-slate-800 bg-slate-900/75 p-4 sm:p-5"><div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold text-slate-100">{{ $teacher->name }}</p><p class="mt-1 text-xs text-slate-500">Faculty</p></div><span class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-400"></span></div><p class="mt-5 text-xs text-slate-400">Next public hours</p><p class="mt-1 text-sm font-medium">{{ $nextPublicTime->date->format('M j') }} · {{ \Illuminate\Support\Carbon::parse($nextPublicTime->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($nextPublicTime->ends_at)->format('g:i A') }}</p>@if($nextPublicTime->location)<p class="mt-1 text-xs text-slate-500">{{ $nextPublicTime->location }}</p>@endif<div class="mt-4 border-t border-slate-800 pt-3"><span class="rounded-full bg-emerald-400/10 px-2.5 py-1 text-[10px] font-semibold text-emerald-200">Published</span></div></article>
                                @empty
                                    <div class="rounded-xl border border-slate-800 bg-slate-900/75 p-5 text-sm text-slate-400 sm:col-span-2 xl:col-span-3">Upcoming published teacher availability will appear on the faculty display here.</div>
                                @endforelse
                            </div>
                            <aside class="flex flex-col items-center justify-center rounded-xl border border-slate-800 bg-slate-900/75 p-5 text-center"><span class="grid h-14 w-14 place-items-center rounded-2xl bg-rose-500/10 text-rose-300"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="3"/><path stroke-linecap="round" d="M7.5 3.5v3m9-3v3m-13 3h17m-12 4h.01m4 0h.01"/></svg></span><p class="mt-4 text-sm font-semibold">Check availability</p><p class="mt-1 text-xs leading-5 text-slate-500">Students can sign in to view slots and request a consultation.</p><a href="{{ route('portal.login') }}" class="mt-4 text-xs font-semibold text-rose-300 hover:text-rose-200">Open ConSync →</a></aside>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-800 px-5 py-3 text-[10px] text-slate-500 sm:px-8"><span>Public availability only · No student names or appointment topics</span><span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-400"></span> Display layout showcase</span></div>
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="border-y border-slate-800 bg-slate-950/55 py-24 sm:py-28">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="mx-auto max-w-2xl text-center"><p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">Designed for your community</p><h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">One school. Three connected roles.</h2><p class="mt-4 leading-7 text-slate-400">Each person gets the tools they need, with schedules and updates flowing through one organized system.</p></div>
                <div class="mt-12 grid gap-4 lg:grid-cols-3">
                    @foreach ([['Students','Find faculty, request consultation times, track appointment status, and keep upcoming meetings close at hand.','student'],['Teachers','Publish availability, review requests, manage consultation schedules, and keep faculty information current.','teacher'],['Administrators','Coordinate people, departments, rooms, school-wide policies, and authorized display devices.','admin']] as [$name,$description,$icon])
                        <article class="rounded-2xl border border-slate-800 bg-slate-900 p-7"><span class="grid h-12 w-12 place-items-center rounded-xl bg-slate-800 text-rose-300">@if ($icon === 'student')<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" d="M5 20a7 7 0 0 1 14 0M4 4h2m12 0h2"/></svg>@elseif ($icon === 'teacher')<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5.5A2.5 2.5 0 0 1 5.5 3H20v16H5.5A2.5 2.5 0 0 0 3 21V5.5Zm0 13A2.5 2.5 0 0 1 5.5 16H20M8 7h8M8 10h6"/></svg>@else<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3 3 7.5l9 4.5 9-4.5L12 3Zm-9 9 9 4.5 9-4.5M3 16.5 12 21l9-4.5"/></svg>@endif</span><h3 class="mt-6 text-lg font-semibold">{{ $name }}</h3><p class="mt-3 text-sm leading-6 text-slate-400">{{ $description }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="mobile" class="py-24 sm:py-28">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 sm:px-8 lg:grid-cols-2 lg:gap-20">
                <div><p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">Your schedule, wherever you are</p><h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">School scheduling that fits in your pocket.</h2><p class="mt-5 leading-7 text-slate-400">Check faculty availability from a phone, tablet, or computer. ConSync is designed for the browser and can support an installable PWA experience on compatible devices.</p><ul class="mt-7 space-y-3 text-sm text-slate-300"><li class="flex gap-3"><span class="text-rose-300">✓</span> Responsive layouts for smaller screens</li><li class="flex gap-3"><span class="text-rose-300">✓</span> Quick access to schedules and status updates</li><li class="flex gap-3"><span class="text-rose-300">✓</span> Web push notifications where supported and enabled</li></ul><p class="mt-5 text-xs leading-5 text-slate-500">Install and notification support depends on device, browser, and future platform configuration.</p></div>
                <div class="mx-auto flex w-full max-w-lg items-center justify-center gap-5">
                    <div class="w-[220px] rounded-[2rem] border border-slate-700 bg-slate-900 p-2 shadow-2xl"><div class="overflow-hidden rounded-[1.5rem] bg-slate-950"><div class="mx-auto mt-2 h-1 w-14 rounded-full bg-slate-800"></div><div class="px-4 pb-5 pt-5"><div class="flex items-center justify-between"><span class="text-xs font-bold">ConSync<span class="text-rose-400">.</span></span><span class="h-2 w-2 rounded-full bg-emerald-400"></span></div><div class="mt-6"><p class="text-[10px] text-slate-500">WEDNESDAY, OCT 14</p><p class="mt-1 text-base font-semibold">Your schedule</p></div><div class="mt-4 space-y-2"><div class="rounded-xl border border-rose-400/20 bg-rose-400/5 p-3"><p class="text-[10px] font-semibold text-rose-200">10:00 AM</p><p class="mt-1 text-[10px] text-slate-200">Consultation request</p><p class="mt-1 text-[9px] text-slate-500">Mathematics · Pending</p></div><div class="rounded-xl border border-slate-800 bg-slate-900 p-3"><p class="text-[10px] font-semibold text-slate-400">1:30 PM</p><p class="mt-1 text-[10px] text-slate-200">Faculty hours</p><p class="mt-1 text-[9px] text-slate-500">Science department</p></div></div><div class="mt-5 rounded-lg bg-rose-500 py-2 text-center text-[10px] font-semibold">Find a teacher</div></div></div></div>
                    <div class="hidden w-60 rounded-2xl border border-slate-700 bg-slate-900 p-4 shadow-xl sm:block"><div class="flex items-center gap-2 border-b border-slate-800 pb-3"><span class="grid h-7 w-7 place-items-center rounded-lg bg-rose-500/10 text-rose-300"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4"/></svg></span><p class="text-xs font-semibold">Appointment status</p></div><p class="mt-3 text-[10px] leading-5 text-slate-400">Requests appear in the teacher’s calendar queue, ready for review.</p><p class="mt-3 text-[9px] text-slate-600">Pending · Confirmed · Completed</p></div>
                </div>
            </div>
        </section>

        <section class="px-5 pb-24 sm:px-8 sm:pb-28">
            <div class="relative mx-auto max-w-7xl overflow-hidden rounded-3xl border border-rose-400/20 bg-slate-900 px-6 py-14 text-center sm:px-12 sm:py-20">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(244,63,94,.14),transparent_58%)]"></div>
                <div class="relative"><p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">Make time for what matters</p><h2 class="mx-auto mt-4 max-w-3xl text-3xl font-bold tracking-tight sm:text-5xl">Make every consultation easier with {{ $siteSettings->name ?: 'ConSync' }}.</h2><p class="mx-auto mt-5 max-w-xl text-sm leading-7 text-slate-400 sm:text-base">Bring your school community into sync with clearer schedules and simpler coordination.</p><a href="{{ route('portal.login') }}" class="focus-rose mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-rose-500 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-rose-400">Get started <span aria-hidden="true">→</span></a></div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-800 bg-slate-950">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:px-8 md:grid-cols-[1.4fr_.7fr_.7fr]">
            <div><a href="#home" class="inline-flex items-center gap-3 text-lg font-bold">@if ($siteSettings->logo_path)<img src="{{ asset('storage/'.$siteSettings->logo_path) }}" alt="" class="h-9 w-9 rounded object-contain">@else<span class="grid h-9 w-9 place-items-center rounded-lg bg-rose-500 text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="3"/><path stroke-linecap="round" d="M7.5 3.5v3m9-3v3m-13 3h17"/></svg></span>@endif {{ $siteSettings->name ?: 'ConSync' }}<span class="text-rose-400">.</span></a><p class="mt-4 max-w-sm text-sm leading-6 text-slate-500">A school consultation and appointment platform connecting students, teachers, and administrators.</p></div>
            <div><h2 class="text-sm font-semibold text-slate-200">Explore</h2><ul class="mt-4 space-y-3 text-sm text-slate-500"><li><a class="hover:text-slate-200" href="#features">Features</a></li><li><a class="hover:text-slate-200" href="#how-it-works">How it works</a></li><li><a class="hover:text-slate-200" href="#calendar">Faculty calendar</a></li><li><a class="hover:text-slate-200" href="#tv-display">TV display</a></li></ul></div>
            <div><h2 class="text-sm font-semibold text-slate-200">Account</h2><ul class="mt-4 space-y-3 text-sm text-slate-500"><li><a class="hover:text-slate-200" href="{{ route('portal.login') }}">Log in</a></li><li><a class="hover:text-slate-200" href="{{ route('admin.login') }}">Admin log in</a></li><li><a class="hover:text-slate-200" href="#about">About ConSync</a></li><li><a class="hover:text-slate-200" href="mailto:{{ config('mail.from.address') }}">Contact</a></li></ul></div>
        </div>
        <div class="border-t border-slate-800"><div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-5 text-xs text-slate-600 sm:px-8 md:flex-row md:items-center md:justify-between"><span>© {{ now()->year }} {{ $siteSettings->name ?: 'ConSync' }}. For authorized school communities.</span><span>Privacy and access policies are set by your institution.</span></div></div>
    </footer>

    <script>
        (() => {
            const button = document.getElementById('mobile-menu-button');
            const menu = document.getElementById('mobile-menu');
            const openIcon = document.getElementById('menu-open-icon');
            const closeIcon = document.getElementById('menu-close-icon');
            button?.addEventListener('click', () => {
                const isOpen = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', String(!isOpen));
                button.setAttribute('aria-label', isOpen ? 'Open navigation menu' : 'Close navigation menu');
                menu?.classList.toggle('hidden', isOpen);
                openIcon?.classList.toggle('hidden', !isOpen);
                closeIcon?.classList.toggle('hidden', isOpen);
            });
            menu?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
                menu.classList.add('hidden');
                button?.setAttribute('aria-expanded', 'false');
                button?.setAttribute('aria-label', 'Open navigation menu');
                openIcon?.classList.remove('hidden');
                closeIcon?.classList.add('hidden');
            }));
        })();
    </script>
</body>
</html>
