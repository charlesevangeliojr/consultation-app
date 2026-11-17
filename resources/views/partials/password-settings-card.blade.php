<section id="account-password-settings" class="mt-6 rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-6" aria-labelledby="change-password-heading">
    <h3 id="change-password-heading" class="text-lg font-semibold">Change password</h3>
    <p class="mt-2 text-xs leading-6 text-slate-400">Update your sign-in password. Use at least 8 characters.</p>
    <form method="POST" action="{{ route('consultations.settings.password') }}" class="mt-5 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label for="current_password" class="mb-2 block text-sm font-medium">Current password</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-sm">
            @error('current_password')<p role="alert" class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="new_password" class="mb-2 block text-sm font-medium">New password</label>
                <input id="new_password" name="password" type="password" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-sm">
                @error('password')<p role="alert" class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-medium">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-sm">
            </div>
        </div>
        <button type="submit" class="min-h-11 w-full rounded-lg bg-rose-500 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-400 sm:w-auto">Update password</button>
    </form>
</section>
