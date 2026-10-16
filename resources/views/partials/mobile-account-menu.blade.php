@php
    $accountUser = auth()->user();
    $accountPhoto = $accountUser->isTeacher() ? $accountUser->teacher?->profile_photo_path : ($accountUser->isStudent() ? $accountUser->student?->profile_photo_path : null);
@endphp
<details class="mobile-account-menu" data-mobile-account-menu>
    <summary class="mobile-account-trigger" aria-label="Open account menu">
        @if($accountPhoto)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($accountPhoto) }}" alt="Your profile picture">
        @else
            <span class="mobile-account-initial" aria-hidden="true">{{ mb_substr($accountUser->name, 0, 1) }}</span>
        @endif
    </summary>
    <div class="mobile-account-panel">
        <div class="mobile-account-identity"><p>Signed in as</p><strong>{{ $accountUser->name }}</strong></div>
        <form method="POST" action="{{ $mobileLogoutUrl ?? route('consultations.logout') }}">
            @csrf
            <button type="submit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5H4v14h5M14 8l4 4-4 4M9 12h12"/></svg>Sign out</button>
        </form>
    </div>
</details>
