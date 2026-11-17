@php
    $manualAppName = $siteSettings->name ?: 'ConSync';
    $manualSections = [
        'student' => [
            'label' => 'Student manual',
            'summary' => 'Log in with your 12-digit LRN, browse approved teachers, book a time window, and track approvals in Consultations.',
            'steps' => [
                [
                    'title' => 'Open your Dashboard',
                    'text' => 'After signing in with your LRN and password, view your scheduled days, upcoming sessions, and booking history.',
                    'where' => 'Dashboard · bottom navigation or desktop sidebar',
                    'desktop' => 'images/tour/student-step-1-desktop.png',
                    'mobile' => 'images/tour/student-step-1-mobile.png',
                    'alt' => $manualAppName.' student dashboard screen showing upcoming consultations and summary cards',
                ],
                [
                    'title' => 'Browse teachers & pick a time',
                    'text' => 'Select an approved teacher, pick a start and end time within their published window (or propose another time), and add your reason.',
                    'where' => 'Calendar · bottom navigation or desktop sidebar',
                    'desktop' => 'images/tour/student-step-2-desktop.png',
                    'mobile' => 'images/tour/student-step-2-mobile.png',
                    'alt' => $manualAppName.' browse calendar screen showing teacher details and booking time range form',
                ],
                [
                    'title' => 'Track booking decisions',
                    'text' => 'Check Pending, Confirmed, or Rejected statuses in one place. Cancel an upcoming booking if you need to pick another time.',
                    'where' => 'Consultations · bottom navigation or desktop sidebar',
                    'desktop' => 'images/tour/student-step-3-desktop.png',
                    'mobile' => 'images/tour/student-step-3-mobile.png',
                    'alt' => $manualAppName.' student consultations screen showing booking statuses and cancel option',
                ],
                [
                    'title' => 'Keep your profile updated',
                    'text' => 'Upload your profile photo and keep your contact number and address accurate. Use Notifications in your menu for push alerts.',
                    'where' => 'My profile · bottom navigation or desktop account menu',
                    'desktop' => 'images/tour/student-step-4-desktop.png',
                    'mobile' => 'images/tour/student-step-4-mobile.png',
                    'alt' => $manualAppName.' student profile screen showing photo uploader and personal details',
                ],
            ],
        ],
        'teacher' => [
            'label' => 'Teacher manual',
            'summary' => 'Complete your profile, wait for admin scheduling approval, publish available windows, and confirm student requests.',
            'steps' => [
                [
                    'title' => 'Complete your Teacher profile',
                    'text' => 'Upload your photo, contact details, address, and what you offer so students know how you can help.',
                    'where' => 'My profile · bottom navigation or desktop account menu',
                    'desktop' => 'images/tour/teacher-step-1-desktop.png',
                    'mobile' => 'images/tour/teacher-step-1-mobile.png',
                    'alt' => $manualAppName.' teacher profile screen showing photo upload and personal details',
                ],
                [
                    'title' => 'Set duration & approval preferences',
                    'text' => 'Choose your maximum consultation length in minutes, manual or automatic confirmation, and queue-name visibility.',
                    'where' => 'Settings · bottom navigation or desktop sidebar',
                    'desktop' => 'images/tour/teacher-step-2-desktop.png',
                    'mobile' => 'images/tour/teacher-step-2-mobile.png',
                    'alt' => $manualAppName.' teacher consultation settings screen',
                ],
                [
                    'title' => 'Publish available consultation hours',
                    'text' => 'Once an administrator approves scheduling, publish future date and time windows with an optional room or office location.',
                    'where' => 'Availability · bottom navigation or desktop sidebar',
                    'desktop' => 'images/tour/teacher-step-3-desktop.png',
                    'mobile' => 'images/tour/teacher-step-3-mobile.png',
                    'alt' => $manualAppName.' teacher availability publishing screen',
                ],
                [
                    'title' => 'Confirm or reject student requests',
                    'text' => 'Review pending and proposed-time requests, check student reasons, and confirm, reject, or mark completed sessions.',
                    'where' => 'Requests · bottom navigation or desktop sidebar',
                    'desktop' => 'images/tour/teacher-step-4-desktop.png',
                    'mobile' => 'images/tour/teacher-step-4-mobile.png',
                    'alt' => $manualAppName.' teacher requests screen with confirm and reject buttons',
                ],
                [
                    'title' => 'Check your monthly schedule',
                    'text' => 'Review confirmed appointments across the month and replay the interactive navigation tour anytime from the Tour button.',
                    'where' => 'Calendar · bottom navigation or desktop sidebar',
                    'desktop' => 'images/tour/teacher-step-5-desktop.png',
                    'mobile' => 'images/tour/teacher-step-5-mobile.png',
                    'alt' => $manualAppName.' teacher monthly calendar view',
                ],
            ],
        ],
        'admin' => [
            'label' => 'Admin manual',
            'summary' => 'Manage accounts, approve teacher scheduling permission, configure grades and sections, and monitor consultation activity.',
            'steps' => [
                [
                    'title' => 'Review accounts & approve teachers',
                    'text' => 'Filter Admins, Teachers, and Students, inspect account details, and approve or revoke teacher scheduling permission.',
                    'where' => 'Accounts · admin sidebar or mobile dock',
                    'desktop' => 'images/tour/admin-step-1-desktop.png',
                    'mobile' => 'images/tour/admin-step-1-mobile.png',
                    'alt' => $manualAppName.' admin accounts list highlighting pending teacher approval',
                ],
                [
                    'title' => 'Configure Grades & sections',
                    'text' => 'Choose which Grades 7–12 appear in student registration and manage sections under each grade level.',
                    'where' => 'Grades & sections · admin sidebar or mobile dock',
                    'desktop' => 'images/tour/admin-step-2-desktop.png',
                    'mobile' => 'images/tour/admin-step-2-mobile.png',
                    'alt' => $manualAppName.' admin grades and sections configuration screen',
                ],
                [
                    'title' => 'Monitor school consultations',
                    'text' => 'View published windows and read-only booking records across the school calendar. Teachers still control booking approval.',
                    'where' => 'Consultations · admin sidebar or mobile dock',
                    'desktop' => 'images/tour/admin-step-3-desktop.png',
                    'mobile' => 'images/tour/admin-step-3-mobile.png',
                    'alt' => $manualAppName.' admin consultation monitoring calendar and booking records',
                ],
            ],
        ],
    ];
@endphp

<section id="user-manual" class="border-t border-slate-800 bg-slate-900/20 py-16 sm:py-24" data-landing-manual aria-labelledby="user-manual-heading">
    <div class="mx-auto max-w-7xl px-5 sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-widest text-rose-300">Visual user manual</p>
                <h2 id="user-manual-heading" class="mt-3 text-3xl font-bold sm:text-4xl">How {{ $manualAppName }} works, screen by screen.</h2>
                <p class="mt-4 leading-7 text-slate-400">Preview every step with real app screenshots. Switch roles or toggle between Desktop and Mobile layouts.</p>
            </div>
            <div class="landing-manual-controls">
                <div class="landing-manual-tabs" role="tablist" aria-label="User manual roles">
                    @foreach($manualSections as $roleKey => $section)
                        <button type="button" id="manual-tab-{{ $roleKey }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" aria-controls="manual-panel-{{ $roleKey }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-manual-role="{{ $roleKey }}">{{ $section['label'] }}</button>
                    @endforeach
                </div>
                <div class="landing-manual-device" role="group" aria-label="Screenshot device view">
                    <button type="button" data-manual-device="desktop" aria-pressed="true">Desktop</button>
                    <button type="button" data-manual-device="mobile" aria-pressed="false">Mobile</button>
                </div>
            </div>
        </div>

        @foreach($manualSections as $roleKey => $section)
            <div id="manual-panel-{{ $roleKey }}" role="tabpanel" aria-labelledby="manual-tab-{{ $roleKey }}" data-manual-panel="{{ $roleKey }}" @unless($loop->first) hidden @endunless class="mt-8">
                <p class="text-sm text-slate-300">{{ $section['summary'] }}</p>
                <ol class="mt-5 grid gap-5 md:grid-cols-2">
                    @foreach($section['steps'] as $step)
                        <li class="landing-manual-card">
                            <div class="landing-manual-card-head">
                                <span class="landing-manual-step-badge">Step {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="landing-manual-where">{{ $step['where'] }}</span>
                            </div>
                            <h3 class="mt-3 text-lg font-semibold">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-400">{{ $step['text'] }}</p>
                            <button type="button" class="landing-manual-shot" data-manual-zoom data-step-title="{{ $step['title'] }}" data-step-text="{{ $step['text'] }}" data-desktop-src="{{ asset($step['desktop']) }}" data-mobile-src="{{ asset($step['mobile']) }}" aria-label="Enlarge screenshot for {{ $step['title'] }}">
                                <img src="{{ asset($step['desktop']) }}" data-manual-img data-desktop-src="{{ asset($step['desktop']) }}" data-mobile-src="{{ asset($step['mobile']) }}" data-mode="desktop" alt="{{ $step['alt'] }}" loading="lazy" decoding="async">
                                <span class="landing-manual-shot-bar"><span data-manual-mode-label>Desktop screen</span><span>Tap to enlarge →</span></span>
                            </button>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endforeach
    </div>

    <dialog id="landing-manual-lightbox" class="landing-manual-lightbox" data-manual-lightbox aria-labelledby="manual-lightbox-title">
        <header class="landing-manual-lightbox-head">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-rose-300">User manual preview</p>
                <h3 id="manual-lightbox-title" class="mt-1 text-lg font-bold" data-manual-lightbox-title></h3>
            </div>
            <div class="flex items-center gap-2">
                <div class="landing-manual-device" role="group" aria-label="Enlarged screenshot view">
                    <button type="button" data-manual-lightbox-device="desktop" aria-pressed="true">Desktop</button>
                    <button type="button" data-manual-lightbox-device="mobile" aria-pressed="false">Mobile</button>
                </div>
                <button type="button" class="landing-manual-lightbox-close" data-manual-lightbox-close aria-label="Close screenshot preview">×</button>
            </div>
        </header>
        <p class="mt-2 text-sm leading-6 text-slate-400" data-manual-lightbox-text></p>
        <div class="landing-manual-lightbox-stage">
            <img src="" alt="" data-manual-lightbox-img data-mode="desktop">
        </div>
    </dialog>
</section>
