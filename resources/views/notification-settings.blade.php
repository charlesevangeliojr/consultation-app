@extends($notificationLayout)
@section('title', 'Notification settings')
@section('content')
<div class="mx-auto max-w-3xl">
    <p class="text-xs font-semibold uppercase tracking-widest text-rose-300">Your device, your choice</p>
    <h2 class="mt-1 text-2xl font-bold sm:mt-2 sm:text-3xl">Notification settings</h2>
    <a href="{{ route('notifications.index') }}" class="mt-1.5 inline-flex min-h-11 items-center text-sm font-semibold text-rose-300 sm:mt-2">← Back to notification inbox</a>
    @if(session('status'))<p role="status" class="mt-4 rounded-lg border border-emerald-800 p-4 text-sm text-emerald-300">{{ session('status') }}</p>@endif
    @if(auth()->user()->isStudent())
        <section class="mt-6 rounded-xl border border-slate-700 bg-slate-900 p-5" aria-labelledby="reminder-heading">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 id="reminder-heading" class="text-lg font-semibold">Consultation reminder</h3>
                @if($remindersReady && $reminderMinutes)
                    <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-300">Active: {{ $reminderMinutes }} min before</span>
                @endif
            </div>
            <p class="mt-2 text-sm leading-6 text-slate-400">Set your reminder once and it will automatically apply to all current and future confirmed consultations—even if you don’t have a booking yet. Enter 1–1,440 minutes (up to 24 hours), or leave blank to turn reminders off.</p>
            @if($remindersReady)
                <form method="POST" action="{{ route('notifications.reminder.update') }}" class="mt-4">
                    @csrf @method('PUT')
                    <label for="minutes_before" class="block text-sm font-medium">Minutes before the start</label>
                    <input id="minutes_before" name="minutes_before" type="number" min="1" max="1440" value="{{ old('minutes_before', $reminderMinutes) }}" class="mt-2 w-full max-w-xs rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">
                    @error('minutes_before')<p role="alert" class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
                    <button class="mt-3 block min-h-11 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Save reminder</button>
                </form>
            @else
                <p role="alert" class="mt-3 text-sm text-amber-300">Reminder database setup is missing. Ask the school administrator to apply the latest migrations.</p>
            @endif
            <p class="mt-3 text-xs leading-6 text-slate-400">Requires a running server scheduler. To receive alerts with the app closed, also enable device notifications below. Delivery is best-effort and may be late or missed.</p>
        </section>
    @endif
    <p class="mt-4 text-sm leading-7 text-slate-400">Receive browser notifications for consultation requests, decisions, cancellations, and scheduling permission updates—even when this app is closed. Availability depends on your device, browser, network, and notification settings.</p>
    @include('partials.notification-device-settings')
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <section class="rounded-xl border border-slate-800 p-5"><h3 class="font-semibold">iPhone &amp; iPad</h3><p class="mt-3 text-sm leading-7 text-slate-400">Requires iOS/iPadOS 16.4 or newer. Add the app to your Home Screen in Safari, open that installed app, sign in, then enable notifications here. Allow notifications when iOS asks.</p></section>
        <section class="rounded-xl border border-slate-800 p-5"><h3 class="font-semibold">Android &amp; desktop</h3><p class="mt-3 text-sm leading-7 text-slate-400">Use a browser that supports Web Push and allow this site’s notifications. Notifications can appear with the tab closed, but force-stopping the browser, blocking background activity, or Focus/Do Not Disturb can prevent alerts.</p></section>
    </div>
    <p class="mt-6 text-sm leading-7 text-slate-400">For privacy, alerts do not include names, LRN, appointment times, or consultation reasons. Open your account to see details. These are event updates, not scheduled reminders. Delivery is best-effort, so always check Consultations or Requests for the current status.</p>
</div>
@endsection
