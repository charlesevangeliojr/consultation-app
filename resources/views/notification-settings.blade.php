@extends($notificationLayout)
@section('title', 'Notification settings')
@section('content')
<div class="mx-auto max-w-3xl">
    <p class="text-xs font-semibold uppercase tracking-widest text-rose-300">Your device, your choice</p>
    <h2 class="mt-3 text-3xl font-bold">Notification settings</h2>
    <a href="{{ route('notifications.index') }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-rose-300">← Back to notification inbox</a>
    <p class="mt-4 text-sm leading-7 text-slate-400">Receive browser notifications for consultation requests, decisions, cancellations, and scheduling permission updates—even when this app is closed. Availability depends on your device, browser, network, and notification settings.</p>
    @include('partials.notification-device-settings')
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <section class="rounded-xl border border-slate-800 p-5"><h3 class="font-semibold">iPhone &amp; iPad</h3><p class="mt-3 text-sm leading-7 text-slate-400">Requires iOS/iPadOS 16.4 or newer. Add the app to your Home Screen in Safari, open that installed app, sign in, then enable notifications here. Allow notifications when iOS asks.</p></section>
        <section class="rounded-xl border border-slate-800 p-5"><h3 class="font-semibold">Android &amp; desktop</h3><p class="mt-3 text-sm leading-7 text-slate-400">Use a browser that supports Web Push and allow this site’s notifications. Notifications can appear with the tab closed, but force-stopping the browser, blocking background activity, or Focus/Do Not Disturb can prevent alerts.</p></section>
    </div>
    <p class="mt-6 text-sm leading-7 text-slate-400">For privacy, alerts do not include names, LRN, appointment times, or consultation reasons. Open your account to see details. These are event updates, not scheduled reminders. Delivery is best-effort, so always check Consultations or Requests for the current status.</p>
</div>
@endsection
