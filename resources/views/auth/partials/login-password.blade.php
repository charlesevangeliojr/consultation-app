<div>
    <label for="password" class="mb-2 block text-sm font-medium">Password</label>
    <div class="relative">
        <input id="password" name="password" type="password" required autocomplete="current-password"
            class="w-full min-w-0 rounded-lg border border-slate-700 bg-slate-950 py-3 pl-4 pr-14 text-base outline-none focus:border-rose-400">
        <button type="button" data-login-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false"
            class="absolute inset-y-0 right-1 flex min-h-11 w-11 items-center justify-center rounded-lg text-slate-400 hover:text-rose-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-rose-400">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                <circle cx="12" cy="12" r="3"/>
                <path data-password-slash class="hidden" d="m3 3 18 18"/>
            </svg>
        </button>
    </div>
</div>
