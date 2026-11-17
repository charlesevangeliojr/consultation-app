@extends('layouts.consultations')
@section('title', 'Settings')
@section('content')
    @include('student.partials.page-header', ['heading' => 'Settings', 'description' => 'Manage consultation reminders, device notifications, and your account security.'])
    <div class="mt-6 max-w-3xl space-y-6">
        <section id="student-settings" class="rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-6" aria-labelledby="student-reminder-heading">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="student-reminder-heading" class="text-lg font-semibold">Consultation reminder</h2>
                @if($remindersReady && $reminderMinutes)
                    <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-300">Active: {{ $reminderMinutes }} min before</span>
                @endif
            </div>
            <p class="mt-2 text-sm leading-6 text-slate-400">Set your reminder once and it will automatically apply to all current and future confirmed consultations—even if you don’t have a booking yet. Enter 1–1,440 minutes (up to 24 hours), or leave blank to turn reminders off.</p>
            @if($remindersReady)
                <form method="POST" action="{{ route('notifications.reminder.update') }}" class="mt-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="return_to" value="consultations.student.settings">
                    <label for="minutes_before" class="mb-2 block text-sm font-medium">Minutes before start</label>
                    <div class="flex flex-wrap items-center gap-3">
                        <input id="minutes_before" name="minutes_before" type="number" min="1" max="1440" value="{{ old('minutes_before', $reminderMinutes) }}" placeholder="e.g. 30" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3 text-sm sm:max-w-xs">
                        <button type="submit" class="min-h-11 w-full rounded-lg bg-rose-500 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-400 sm:w-auto">Save reminder</button>
                    </div>
                    @error('minutes_before')<p role="alert" class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
                </form>
            @else
                <p role="alert" class="mt-3 text-sm text-amber-300">Reminder database setup is missing. Ask the school administrator to apply the latest migrations.</p>
            @endif
        </section>

        @include('partials.notification-device-settings')

        @include('partials.password-settings-card')

        <section class="rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-6" aria-labelledby="student-quick-links-heading">
            <h3 id="student-quick-links-heading" class="text-lg font-semibold">Account &amp; navigation guide</h3>
            <p class="mt-2 text-xs leading-6 text-slate-400">Switch between dark and light themes from the top bar anytime, check your notification inbox, or replay the quick tour.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('notifications.index') }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">Notification inbox →</a>
                <a href="{{ route('consultations.student-profile.edit') }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">Edit profile →</a>
                <button type="button" data-portal-tour-start class="min-h-11 rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">Replay quick tour</button>
            </div>
        </section>
    </div>
@endsection
