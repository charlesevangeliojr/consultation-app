<section id="home" class="landing-hero">
    <div class="landing-hero-grid">
        <div class="landing-hero-copy">
            <p class="landing-hero-eyebrow"><span aria-hidden="true"></span>One school. One consultation system.</p>
            <p class="landing-hero-kicker">A little time can make a big difference.</p>
            <h1>Big questions.<br><span>Let’s make time.</span></h1>
            <p class="landing-hero-description">A simpler way for students to reach teachers—and for teachers to make room for support. Request a consultation, track the decision, and know what comes next.</p>
            <div class="landing-hero-actions">
                <a href="{{ route('register') }}" class="focus-rose landing-hero-primary">Register →</a>
                <a href="{{ route('portal.login') }}" class="focus-rose landing-hero-secondary">Already registered? Log in</a>
            </div>
            <a href="#how-it-works" class="focus-rose landing-hero-guide">New here? See how it works <span aria-hidden="true">↓</span></a>
            <div class="landing-hero-notes">
                <p><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg><span>School time, always<br><strong>Philippine Standard Time · UTC+8</strong></span></p>
                <p><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="3"/><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><span>Your consultation reason<br><strong>Hidden from other students</strong></span></p>
            </div>
        </div>

        <aside class="landing-hero-path" aria-labelledby="hero-path-heading" data-landing-role-switcher>
            <div class="landing-hero-path-header"><span class="landing-hero-path-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="16" rx="4"/><path stroke-linecap="round" d="M8 3v4m8-4v4M3 11h18m-13 5 2 2 5-5"/></svg></span><div><p>From question to conversation</p><h2 id="hero-path-heading">Your role. Your next step.</h2></div></div>
            <div class="landing-hero-role-tabs" role="tablist" aria-label="Explore your role">
                <button id="hero-student-tab" type="button" role="tab" aria-selected="true" aria-controls="hero-student-panel" data-landing-role="student">I’m a student</button>
                <button id="hero-teacher-tab" type="button" role="tab" aria-selected="false" aria-controls="hero-teacher-panel" tabindex="-1" data-landing-role="teacher">I’m a teacher</button>
            </div>
            <div id="hero-student-panel" class="landing-hero-role-panel" role="tabpanel" aria-labelledby="hero-student-tab" tabindex="0" data-landing-role-panel="student">
                <ol class="landing-hero-journey">
                    <li><span aria-hidden="true">01</span><div><h3>Find the right teacher.</h3><p>Read what they offer and explore their published consultation hours.</p></div></li>
                    <li><span aria-hidden="true">02</span><div><h3>Ask for a time that works.</h3><p>Choose a valid time range and add a reason for your consultation.</p></div></li>
                    <li><span aria-hidden="true">03</span><div><h3>Know what happens next.</h3><p>Check Consultations. Pending means waiting; Confirmed means accepted.</p></div></li>
                </ol>
                <div class="landing-hero-login-note"><span>Your student login</span><strong>12-digit LRN and password</strong><p>Student email is not required at registration.</p></div>
                <a href="{{ route('register', ['role' => 'student']) }}" class="focus-rose landing-hero-role-link">Register as a student →</a>
            </div>
            <div id="hero-teacher-panel" class="landing-hero-role-panel" role="tabpanel" aria-labelledby="hero-teacher-tab" tabindex="0" data-landing-role-panel="teacher" hidden>
                <ol class="landing-hero-journey">
                    <li><span aria-hidden="true">01</span><div><h3>Get ready to offer support.</h3><p>Complete your profile and get admin scheduling approval before publishing availability.</p></div></li>
                    <li><span aria-hidden="true">02</span><div><h3>Make your available time clear.</h3><p>Publish your hours and set the maximum consultation length in minutes.</p></div></li>
                    <li><span aria-hidden="true">03</span><div><h3>Manage requests your way.</h3><p>Set manual or automatic booking approval. Proposed times always need your acceptance.</p></div></li>
                </ol>
                <div class="landing-hero-login-note"><span>Your teacher login</span><strong>registered email and password</strong><p>Profile completion and scheduling approval are separate.</p></div>
                <a href="{{ route('register', ['role' => 'teacher']) }}" class="focus-rose landing-hero-role-link">Register as a teacher →</a>
            </div>
            <p class="landing-hero-tour-note"><span aria-hidden="true">✦</span> A guided tour helps you settle in on your first login.</p>
        </aside>
    </div>
</section>
