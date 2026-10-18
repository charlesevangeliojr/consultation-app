<section class="account-panel mt-8" aria-labelledby="account-activity-heading">
    <h3 id="account-activity-heading" class="account-panel-heading">Account activity &amp; status</h3>
    <div class="account-panel-body account-fields">
        <div><p class="account-help">Online status</p><p class="mt-1 font-semibold {{ $account->isOnline() ? 'text-emerald-300' : 'text-slate-300' }}">{{ $account->isOnline() ? 'Online' : 'Offline' }}</p><p class="account-help">Online means authenticated activity within the last 5 minutes.</p></div>
        <div><p class="account-help">Last seen</p><p class="mt-1">{{ $account->last_seen_at ? $account->last_seen_at->format('M j, Y g:i A').' '.config('app.timezone') : 'Not yet recorded' }}</p></div>
        @if($account->isStudent())
            <form method="POST" action="{{ route('admin.accounts.status', $account) }}" class="space-y-3">
                @csrf @method('PATCH')
                <label for="account-status">Account status</label>
                <select id="account-status" name="status" class="account-input" required>
                    @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $account->student?->status ?? 'active') === $value)>{{ $label }}</option>@endforeach
                </select>
                @error('status')<p class="account-error">{{ $message }}</p>@enderror
                <p class="account-help">Student accounts become inactive after 2 years without activity. This does not indicate graduation or online presence. Reactivating starts a new 2-year inactivity period.</p>
                <button class="account-button account-button-primary">Save account status</button>
            </form>
        @endif
    </div>
</section>
