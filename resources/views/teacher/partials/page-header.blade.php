<header @class(['flex flex-wrap items-start justify-between gap-4', 'teacher-header-compact' => $compactMobile ?? false])>
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">Teacher portal</p>
        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">{{ $heading }}</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">{{ $description }}</p>
    </div>
    @isset($actionUrl)<a href="{{ $actionUrl }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-rose-500 px-4 py-2.5 text-sm font-semibold hover:bg-rose-400">{{ $actionLabel }}</a>@endisset
</header>
@if(! $profile->consultation_approved)
    <p role="status" class="mt-5 rounded-xl border border-amber-700 bg-amber-950/40 p-4 text-sm text-amber-200">Your consultation permission is awaiting admin approval or has been revoked. You can review your schedule and manage existing bookings, but cannot publish availability or confirm new requests.</p>
@endif
@if($errors->any())
    <div role="alert" class="mt-5 rounded-xl border border-rose-900 bg-rose-950/40 p-4 text-sm text-rose-200">@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
@endif
