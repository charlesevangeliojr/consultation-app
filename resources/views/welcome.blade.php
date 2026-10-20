<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A consultation system for one school. Learn how students register and book, teachers manage requests, and administrators maintain the school’s accounts and settings.">
    <title>{{ $siteSettings->name ?: 'ConSync' }} — Your guide to school consultations</title>
    <link rel="icon" href="{{ $siteSettings->favicon_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->favicon_path) : asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-umd.js" defer></script>
    <script src="{{ asset('js/landing.js') }}" defer data-turbo-eval="false"></script>
    <style>
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        section[id] { scroll-margin-top: 100px; }
        .hero-glow { background: radial-gradient(ellipse at 80% 10%, rgb(244 63 94 / 14%), transparent 55%); }
        .focus-rose:focus-visible, main :is(a, summary):focus-visible { outline: 2px solid #fb7185; outline-offset: 4px; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; } }
    </style>
    @include('partials.theme-head')
</head>
<body class="bg-slate-950 text-slate-100 antialiased selection:bg-rose-500/30">
    @include('partials.pwa-ui')
    @php
        $appName = $siteSettings->name ?: 'ConSync';
        $links = ['how-it-works' => 'How to use', 'features' => 'Features', 'calendar' => 'Public preview', 'faq' => 'Questions'];
        $monthStart = now()->startOfMonth();
        $monthOffset = $monthStart->dayOfWeekIso - 1;
        $calendarCells = (int) ceil(($monthOffset + $monthStart->daysInMonth) / 7) * 7;
        $previewDates = $publicAvailability->pluck('date')->map(fn ($date) => $date->toDateString())->unique()->all();
    @endphp
    <a href="#main" class="focus-rose sr-only z-50 rounded-lg bg-slate-900 px-4 py-3 text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
    <header class="sticky top-0 z-40 border-b border-slate-800 bg-slate-950/95 backdrop-blur-xl">
        <nav aria-label="Main navigation" class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-8">
            <a href="#home" class="focus-rose flex min-w-0 items-center gap-3 rounded-lg lg:flex-1" aria-label="{{ $appName }} home">
                @if($siteSettings->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo_path) }}" alt="" class="h-10 w-10 shrink-0 rounded-lg object-contain">
                @else
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-rose-500 text-lg font-bold text-white" aria-hidden="true">{{ mb_substr($appName, 0, 1) }}</span>
                @endif
                <span class="truncate text-lg font-bold tracking-tight">{{ $appName }}<span class="text-rose-400">.</span></span>
            </a>
            <div class="hidden shrink-0 items-center gap-4 lg:flex xl:gap-6">
                @foreach($links as $id => $label)<a href="#{{ $id }}" class="focus-rose text-sm text-slate-300 hover:text-white">{{ $label }}</a>@endforeach
                <a href="{{ route('register') }}" class="focus-rose rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold">Register</a>
                <a href="{{ route('portal.login') }}" class="focus-rose rounded-lg bg-rose-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-400">Log in</a>
            </div>
            <div class="theme-navbar-actions">
                @include('partials.theme-toggle')
                <button type="button" data-landing-menu-button aria-expanded="false" aria-controls="landing-mobile-menu" aria-label="Open navigation menu" class="focus-rose grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-slate-700 lg:hidden"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
            </div>
        </nav>
        <div id="landing-mobile-menu" class="border-t border-slate-800 bg-slate-950 px-4 py-4 lg:hidden" hidden>
            <div class="mx-auto grid max-w-7xl gap-2">
                @foreach($links as $id => $label)<a href="#{{ $id }}" class="rounded-lg px-3 py-3 text-sm text-slate-300 hover:bg-slate-900">{{ $label }}</a>@endforeach
                <a href="{{ route('register') }}" class="rounded-lg border border-slate-700 px-3 py-3 text-center text-sm font-semibold">Register</a>
                <a href="{{ route('portal.login') }}" class="rounded-lg bg-rose-500 px-3 py-3 text-center text-sm font-semibold text-white">Log in</a>
            </div>
        </div>
    </header>

    <main id="main">
        <section id="home" class="hero-glow border-b border-slate-800">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[1.15fr_.85fr]">
                <div>
                    <p class="inline-flex rounded-full border border-rose-400/20 bg-rose-400/5 px-3 py-2 text-xs font-medium text-rose-300">One school. One consultation system.</p>
                    <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight sm:text-6xl">Need academic help?<br><span class="text-rose-400">Find a time to talk.</span></h1>
                    <p class="mt-6 max-w-xl text-base leading-8 text-slate-400">A dedicated space for this school’s students, teachers, and administrators. Choose a teacher, request a consultation, and check the decision in your account. Teachers manage bookings; administrators manage this school’s settings and teacher scheduling permission.</p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('portal.login') }}" class="focus-rose rounded-xl bg-rose-500 px-6 py-3.5 text-center text-sm font-semibold text-white hover:bg-rose-400">Log in to book →</a>
                        <a href="#how-it-works" class="focus-rose rounded-xl border border-slate-700 bg-slate-900 px-6 py-3.5 text-center text-sm font-semibold">Show me how it works</a>
                    </div>
                    <p class="mt-6 text-xs leading-6 text-slate-400">All appointment times use <strong class="text-slate-200">Philippine Standard Time · Asia/Manila · UTC+8</strong>.</p>
                </div>
                <aside class="rounded-3xl border border-slate-700 bg-slate-900 p-6 shadow-2xl sm:p-8" aria-labelledby="start-heading">
                    <p class="text-xs font-semibold uppercase tracking-widest text-rose-300">Start here</p>
                    <h2 id="start-heading" class="mt-3 text-2xl font-bold">Which account do you use?</h2>
                    <div class="mt-6 space-y-4">
                        <div class="rounded-xl border border-slate-800 bg-slate-950 p-4"><h3 class="font-semibold">Student</h3><p class="mt-2 text-sm leading-6 text-slate-400">Log in with your <strong class="text-slate-200">12-digit LRN and password</strong>. Student email is not required at registration.</p><a href="{{ route('register', ['role' => 'student']) }}" class="focus-rose mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-rose-300">Register as a student →</a></div>
                        <div class="rounded-xl border border-slate-800 bg-slate-950 p-4"><h3 class="font-semibold">Teacher</h3><p class="mt-2 text-sm leading-6 text-slate-400">Log in with your <strong class="text-slate-200">registered email and password</strong>. Complete your profile and get admin scheduling approval before publishing availability.</p><a href="{{ route('register', ['role' => 'teacher']) }}" class="focus-rose mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-rose-300">Register as a teacher →</a></div>
                    </div>
                    <p class="mt-5 text-xs leading-6 text-slate-400">Registration is for this school’s students and teachers. School administrator? Use <a href="{{ route('admin.login') }}" class="focus-rose font-semibold text-rose-300 underline underline-offset-4">Admin log in</a>. Public registration only offers Student and Teacher accounts.</p>
                </aside>
            </div>
        </section>

        <section id="how-it-works" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-24">
            <div class="max-w-2xl"><p class="text-xs font-semibold uppercase tracking-widest text-rose-300">For students</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">Your first consultation, step by step.</h2><p class="mt-4 leading-7 text-slate-400">Sending a request does not always mean it is confirmed. Here is what to do—and where to check next.</p></div>
            <ol class="mt-10 grid gap-4 md:grid-cols-2">
                @foreach([
                    ['Register with your school details', 'Choose Student and enter your name, 12-digit LRN, personal details, and password. Select a school-enabled grade and its matching section. Grades 7–12 are built in, but administrators choose which appear. Ask your school administrator if your grade or section is missing.'],
                    ['Log in and choose a teacher', 'Use your LRN and password at Log in. Dashboard gives an overview. Open Calendar, select an approved teacher, and read their consultation offerings and maximum duration. The first-login tour explains your navigation.'],
                    ['Choose your range and give a reason', 'Pick a future start and end time within the teacher’s published window, up to their maximum in minutes. Add a consultation reason. Your bookings cannot overlap, even across different teachers.'],
                    ['Check the booking decision', 'Published-window bookings are Pending unless the teacher enables automatic confirmation. Proposed times always need teacher acceptance. Open Consultations to check the current status; Pending is not a confirmed appointment.'],
                    ['Attend, or cancel and book again', 'For a confirmed booking, follow the agreed time and any location shown by the teacher. Cancel an upcoming pending or confirmed booking before it starts if needed. There is no student reschedule action: cancel and make a new booking. Teachers can mark confirmed consultations Completed.'],
                    ['Follow updates in Consultations', 'The Consultations badge counts unfinished pending/confirmed bookings plus unseen rejections. A rejection stops counting when you view it in that list. Unfinished bookings stay counted until they end, are completed, or cancelled. Check the page for updates; badges are not push notifications.'],
                ] as [$title, $text])
                    <li class="flex gap-4 rounded-2xl border border-slate-800 bg-slate-900 p-6"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-rose-500/10 text-sm font-bold text-rose-300" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h3 class="font-semibold">{{ $title }}</h3><p class="mt-3 text-sm leading-7 text-slate-400">{{ $text }}</p></div></li>
                @endforeach
            </ol>
            <aside class="mt-6 rounded-2xl border border-rose-400/20 bg-rose-400/5 p-6" aria-labelledby="proposed-heading"><h3 id="proposed-heading" class="font-semibold">No suitable published time?</h3><p class="mt-3 text-sm leading-7 text-slate-400">In Calendar, select a teacher and use <strong class="text-slate-200">Request another time</strong>. Propose a future date and start/end time within the teacher’s duration limit, and add a reason—even if no availability is published. The teacher must accept before the start time. Proposed requests are automatically rejected if unanswered when the start arrives, or if an accepted consultation takes the same teacher’s time.</p></aside>
        </section>

        <section id="about" class="border-y border-slate-800 bg-slate-900/30 py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <p class="text-xs font-semibold uppercase tracking-widest text-rose-300">Different roles, clear responsibilities</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">Teachers handle bookings. Admins handle school setup.</h2>
                <div class="mt-10 grid gap-6 lg:grid-cols-2">
                    <article class="rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8"><h3 class="text-xl font-semibold">Teacher setup &amp; daily workflow</h3><ol class="mt-5 list-decimal space-y-4 pl-5 text-sm leading-7 text-slate-400"><li><strong class="text-slate-200">Finish My profile.</strong> Add your photo, personal/contact details, address, and consultation offerings. Required profile completion is separate from scheduling approval.</li><li><strong class="text-slate-200">Get admin scheduling approval.</strong> Until approved, you cannot publish availability or accept new bookings.</li><li><strong class="text-slate-200">Choose Settings.</strong> Set the maximum consultation time in whole minutes, booking approval mode, and queue-name visibility. School privacy rules override your name-visibility preference.</li><li><strong class="text-slate-200">Publish Availability.</strong> Create a future date and time window, with an optional location. Students select shorter ranges inside it.</li><li><strong class="text-slate-200">Review Requests and Calendar.</strong> Accept or reject pending requests, check confirmed appointments, and mark consultations completed. Proposed times always require manual acceptance.</li></ol><p class="mt-5 rounded-xl border border-slate-700 p-4 text-xs leading-6 text-slate-400">Cancelling an availability window automatically rejects all its pending and confirmed bookings. Revoking scheduling permission blocks new schedules, requests, and confirmations; it does not delete existing records.</p></article>
                    <article class="rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8"><h3 class="text-xl font-semibold">Administrator setup &amp; monitoring</h3><ol class="mt-5 list-decimal space-y-4 pl-5 text-sm leading-7 text-slate-400"><li><strong class="text-slate-200">Use Admin log in.</strong> Available tools depend on the account’s permissions; public registration cannot create administrators.</li><li><strong class="text-slate-200">Configure Grades &amp; sections.</strong> Check which built-in Grades 7–12 students can select at registration, save your choices, and add matching sections. Hiding a grade does not change existing student assignments.</li><li><strong class="text-slate-200">Review Accounts.</strong> Use Admins, Teachers, and Students groups. Teachers needing scheduling approval are highlighted; open their account details to approve or revoke scheduling.</li><li><strong class="text-slate-200">Set school-wide preferences.</strong> Manage branding and queue-name visibility in School settings, and account access in Roles &amp; permissions.</li><li><strong class="text-slate-200">Monitor, do not approve bookings.</strong> Dashboard summarizes activity. Consultations shows calendars and booking records. Only teachers control individual booking decisions.</li></ol><a href="{{ route('admin.login') }}" class="focus-rose mt-5 inline-flex min-h-11 items-center text-sm font-semibold text-rose-300">Open Admin log in →</a></article>
                </div>
            </div>
        </section>

        <section id="features" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-24">
            <p class="text-xs font-semibold uppercase tracking-widest text-rose-300">What is available now</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">Useful tools. Clear limits.</h2>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Flexible booking ranges', 'Choose whole-minute start/end times within published availability and the teacher’s configured maximum. The system checks teacher and student time conflicts.'],
                    ['Proposed-time requests', 'Ask an approved teacher for another future time, even without published availability. Proposed requests always need manual teacher acceptance.'],
                    ['In-app status and badges', 'Review pending, confirmed, rejected, cancelled, and completed records. Teacher Requests badges count upcoming pending requests. There are no email, SMS, or web push reminders in this system.'],
                    ['Privacy-aware queues', 'Consultation reasons are never shown to other students. Other students’ names in a queue depend on school policy and, where allowed, the teacher’s preference. Authorized staff can view booking records.'],
                    ['First-login navigation tours', 'Student and teacher tours explain the actual pages. Replay the guide using Tour in the top navbar, or the question-mark button on mobile.'],
                    ['Mobile, themes & home-screen access', 'Use responsive pages with light/dark themes. Install the web app on supported browsers for home-screen access. Private pages and booking data are not cached for offline use.'],
                ] as [$title, $text])
                    <article class="rounded-2xl border border-slate-800 bg-slate-900 p-6"><span class="text-xs font-semibold text-rose-300" aria-hidden="true">0{{ $loop->iteration }}</span><h3 class="mt-3 font-semibold">{{ $title }}</h3><p class="mt-3 text-sm leading-7 text-slate-400">{{ $text }}</p></article>
                @endforeach
            </div>
        </section>

        <section id="calendar" class="border-y border-slate-800 bg-slate-900/30 py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="max-w-2xl"><p class="text-xs font-semibold uppercase tracking-widest text-rose-300">Public availability preview</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">Published hours—not guaranteed free slots.</h2><p class="mt-4 leading-7 text-slate-400">This preview shows up to 12 current or upcoming published windows from teachers approved for scheduling. It does not show bookings or remaining free time. Sign in to choose a teacher and check what you can actually book. Refresh this page for updated hours.</p></div>
                <div class="mt-10 grid items-start gap-6 lg:grid-cols-[.8fr_1.2fr]">
                    <div class="rounded-2xl border border-slate-700 bg-slate-900 p-5 sm:p-7" aria-label="Published dates preview">
                        <h3 class="text-lg font-semibold">{{ $monthStart->format('F Y') }}</h3>
                        <div class="mt-5 grid grid-cols-7 gap-1 text-center text-xs">
                            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)<span class="py-2 text-[10px] font-medium text-slate-400">{{ $day }}</span>@endforeach
                            @foreach(range(0, $calendarCells - 1) as $cell)
                                @php
                                    $dayNumber = $cell - $monthOffset + 1;
                                    $inMonth = $dayNumber >= 1 && $dayNumber <= $monthStart->daysInMonth;
                                    $date = $inMonth ? $monthStart->copy()->addDays($dayNumber - 1) : null;
                                    $isToday = $date?->isToday();
                                    $hasWindow = $date && in_array($date->toDateString(), $previewDates, true);
                                @endphp
                                <div class="relative grid aspect-square place-items-center rounded-lg {{ $isToday ? 'bg-rose-500 font-semibold text-white' : 'text-slate-300' }}" @if($isToday) aria-current="date" @endif @unless($inMonth) aria-hidden="true" @endunless>
                                    {{ $inMonth ? $dayNumber : '' }}
                                    @if($hasWindow)<span class="absolute bottom-1 h-1 w-1 rounded-full {{ $isToday ? 'bg-white' : 'bg-rose-400' }}" aria-label="Published window in this preview"></span>@endif
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-5 border-t border-slate-800 pt-4 text-xs leading-6 text-slate-400">Dots mark published dates included in this limited preview. No dot does not mean a teacher is unavailable. Highlighted date = today. Times: Asia/Manila (UTC+8).</p>
                    </div>
                    <div class="space-y-3" aria-label="Upcoming published teacher windows">
                        @forelse($publicAvailability as $period)
                            <article class="rounded-xl border border-slate-800 bg-slate-900 p-5"><div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0 flex-1"><h3 class="break-words font-semibold">{{ $period->teacher->name }}</h3><p class="mt-2 text-sm text-slate-400">{{ $period->date->format('D, M j, Y') }} · {{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($period->ends_at)->format('g:i A') }}</p>@if($period->location)<p class="mt-2 break-words text-sm text-slate-400">Location: {{ $period->location }}</p>@endif</div><span class="rounded-full border border-slate-700 px-3 py-1 text-[10px] font-semibold text-slate-300">Published window</span></div></article>
                        @empty
                            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6"><h3 class="font-semibold">No upcoming published hours to preview</h3><p class="mt-3 text-sm leading-7 text-slate-400">Sign in to check approved teachers. If a teacher has no published availability, you can still propose a future time for their approval. If no teacher is listed, contact your school administrator.</p></div>
                        @endforelse
                        <a href="{{ route('portal.login') }}" class="focus-rose inline-flex min-h-11 items-center px-1 text-sm font-semibold text-rose-300">Log in to check bookable times →</a>
                    </div>
                </div>
                <p class="mt-6 text-xs leading-6 text-slate-400">Public preview only: no student names, private consultation reasons, or appointment records are shown here.</p>
            </div>
        </section>

        <section id="faq" class="mx-auto max-w-4xl px-5 py-20 sm:px-8 sm:py-24">
            <p class="text-xs font-semibold uppercase tracking-widest text-rose-300">Before you book</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">Good to know.</h2>
            <div class="mt-8 space-y-3">
                @foreach([
                    ['Is this a multi-school platform?', 'No. This installation serves one school. Students, teachers, administrators, grade and section lists, branding, and scheduling policies belong to that school. There is no school-selection or multi-school management feature. Register only if you are a student or teacher of this school.'],
                    ['What do the booking statuses mean?', 'Pending means waiting for teacher approval; Confirmed means accepted (manually or through automatic confirmation for a published window). Rejected means the request was declined or automatically rejected. Cancelled means the booking was cancelled. Completed is set by the teacher. A past booking is not automatically marked Completed just because its time has passed.'],
                    ['Does a proposed time reserve the teacher’s schedule?', 'Not while it is Pending. It reserves your own requested time against overlapping requests, but other students may propose the same teacher time. Teacher acceptance checks conflicts again. Other overlapping proposals for that teacher are then automatically rejected.'],
                    ['Who can see my consultation reason?', 'You, the teacher, and administrators with permission to view consultation records. Other students cannot see your consultation reason. Queue-name visibility is a separate setting controlled by school policy and, where permitted, the teacher.'],
                    ['Why can’t a teacher publish availability yet?', 'A teacher must complete the required profile and have scheduling permission approved by an administrator. Creating a teacher account alone does not grant scheduling permission. Contact your school administrator if approval is still pending.'],
                    ['Can I change or reschedule my booking?', 'Students cannot directly reschedule. Cancel a pending or confirmed booking before its start, then make a new booking. If a teacher cancels a published availability window, its pending and confirmed bookings are automatically rejected. Check Consultations before attending.'],
                    ['How do I install it on Android or iPhone?', 'Use the website on HTTPS in a supported browser. On Android, tap Install app when the browser offers installation. On iPhone, open the site in Safari, tap Share, then Add to Home Screen and Add. iPhone installation uses these manual steps, not an automatic install prompt. The admin app installs separately with Admin in its name.'],
                    ['Does the installed app work offline or send reminders?', 'An internet connection is required for accounts, schedules, and bookings. Only a generic offline screen is cached—not private pages, photos, or consultation data. Updates are shown inside the app; there are no email, SMS, or web push reminders.'],
                    ['How do I find my way around after login?', 'A tour opens on your first authenticated visit as a student or teacher, before the install suggestion. Finish or skip it once; the choice is saved to your account. Replay it anytime using Tour in the top navbar, or the question-mark button on mobile. Use your avatar menu for profile access and sign out.'],
                ] as [$question, $answer])
                    <details class="rounded-xl border border-slate-800 bg-slate-900 p-5"><summary class="cursor-pointer text-sm font-semibold leading-6">{{ $question }}</summary><p class="mt-3 text-sm leading-7 text-slate-400">{{ $answer }}</p></details>
                @endforeach
            </div>
        </section>

        <section class="px-5 pb-20 sm:px-8">
            <div class="mx-auto max-w-7xl rounded-3xl border border-rose-400/20 bg-slate-900 p-8 text-center sm:p-14"><p class="text-xs font-semibold uppercase tracking-widest text-rose-300">Ready for your next step?</p><h2 class="mt-4 text-3xl font-bold">Start with your account. Stay informed in Consultations.</h2><p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-slate-400">Already registered? Students use an LRN; teachers use their registered email. New here? Choose your role and follow the registration form.</p><div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row"><a href="{{ route('portal.login') }}" class="focus-rose rounded-xl bg-rose-500 px-6 py-3.5 text-sm font-semibold text-white">Log in →</a><a href="{{ route('register') }}" class="focus-rose rounded-xl border border-slate-700 px-6 py-3.5 text-sm font-semibold">Create an account</a></div></div>
        </section>
    </main>
    <footer class="border-t border-slate-800 bg-slate-950">
        <div class="mx-auto flex max-w-7xl flex-col justify-between gap-6 px-5 py-10 sm:px-8 md:flex-row"><div><p class="font-bold">{{ $appName }}</p><p class="mt-2 max-w-md text-xs leading-6 text-slate-400">A single-school consultation system for this school’s students, teachers, and authorized administrators.</p><p class="mt-3 text-xs text-slate-500">© {{ now()->year }} {{ $appName }} · Philippine Standard Time</p><button type="button" data-pwa-install-button class="focus-rose mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-rose-300">Install app</button></div><nav aria-label="Footer navigation" class="flex flex-wrap items-start gap-x-5 gap-y-3 text-sm text-slate-400"><a href="#how-it-works" class="focus-rose">How to use</a><a href="#about" class="focus-rose">Teacher &amp; admin guide</a><a href="#faq" class="focus-rose">Questions</a><a href="{{ route('admin.login') }}" class="focus-rose">Admin log in</a></nav></div>
    </footer>
</body>
</html>
