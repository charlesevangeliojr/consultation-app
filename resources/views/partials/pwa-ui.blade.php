@php($pwaAppName = ($siteSettings->name ?: config('app.name', 'Consultation')).(request()->is('admin', 'admin/*') ? ' Admin' : ''))
<div class="pwa-launch" data-pwa-launch role="status" aria-live="polite" aria-label="Opening app" hidden>
    <img src="{{ $siteSettings->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo_path) : asset('pwa/icon.svg') }}" alt="" width="72" height="72">
    <strong>{{ $pwaAppName }}</strong>
    <span class="pwa-spinner" aria-hidden="true"></span>
    <span>Opening your workspace…</span>
</div>
<dialog class="pwa-install" data-pwa-install aria-labelledby="pwa-install-title" aria-describedby="pwa-install-description" hidden>
    <div class="pwa-install-hero">
        <button type="button" class="pwa-dismiss" data-pwa-dismiss aria-label="Dismiss install suggestion"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        <div class="pwa-install-icon">
            <img src="{{ $siteSettings->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo_path) : asset('pwa/icon.svg') }}" alt="" width="76" height="76">
            <span aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m-4-4 4 4 4-4M5 17v4h14v-4"/></svg></span>
        </div>
        <span class="pwa-install-eyebrow">YOUR SCHOOL, ONE TAP AWAY</span>
        <h2 id="pwa-install-title">Install {{ $pwaAppName }}</h2>
        <p id="pwa-install-description">A little less browsing.<br>A simpler way to stay connected.</p>
    </div>
    <div class="pwa-install-body">
        <ul class="pwa-install-benefits">
            <li><span class="pwa-benefit-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18h4M10 7h4m-2-2v4"/></svg></span><div><strong>Right on your home screen</strong><p>Open your school portal with a single tap.</p></div></li>
            <li><span class="pwa-benefit-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-13 5 3 3 5-5"/></svg></span><div><strong>Your familiar workspace</strong><p>Keep schedules and requests close at hand.</p></div></li>
        </ul>
        <div class="pwa-install-actions">
            <button type="button" data-pwa-install-button>Install app</button>
            <button type="button" class="pwa-install-later" data-pwa-dismiss>Not now</button>
        </div>
        <p class="pwa-install-status" data-pwa-install-status role="status"></p>
        <p class="pwa-install-footnote"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>No APK download. Internet connection required.</p>
    </div>
</dialog>
<dialog class="pwa-instructions" data-pwa-instructions aria-labelledby="pwa-instructions-title">
    <h2 id="pwa-instructions-title">Add to your home screen</h2>
    <p data-pwa-instructions-text>Open your browser menu and choose Install app or Add to Home screen, if available.</p>
    <ol class="pwa-install-steps" data-pwa-install-steps></ol>
    <p>This installs a web app, not an APK. An internet connection is needed for accounts and consultations.</p>
    <button type="button" data-pwa-instructions-close>Got it</button>
</dialog>
