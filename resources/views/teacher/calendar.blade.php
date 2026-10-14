@extends('layouts.consultations')
@section('title', 'Teacher calendar')
@section('content')
<p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">Teacher portal</p><h1 class="mt-3 text-3xl font-bold">Consultation calendar</h1><p class="mt-2 text-slate-400">Publish times students can request. New requests appear below in time order.</p>
<p class="mt-3 text-sm text-slate-400">Subjects: {{ $profile->subjects->pluck('name')->join(', ') ?: 'Ask an administrator to assign your subjects.' }}</p>
@if(! $profile->consultation_approved)<p role="status" class="mt-5 rounded-lg border border-amber-700 bg-amber-950/40 p-4 text-sm text-amber-200">Your consultation permission is awaiting admin approval or has been revoked. You cannot publish availability or accept new bookings yet.</p>@endif
@if($errors->any())<div role="alert" class="mt-5 rounded-lg bg-rose-950/40 p-4 text-sm text-rose-200">@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>@endif
<section class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-7">
    <h2 class="text-lg font-semibold">Consultation settings</h2>
    <form method="POST" action="{{ route('consultations.teacher.settings') }}" class="mt-5 grid gap-4 sm:grid-cols-3">@csrf @method('PUT')
        <div><label for="default-slot-minutes" class="mb-2 block text-sm">Default slot duration (minutes)</label><input id="default-slot-minutes" name="slot_minutes" type="number" required min="5" max="240" value="{{ old('slot_minutes', $profile->slot_minutes) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5"></div>
        <div><label for="auto-approve" class="mb-2 block text-sm">Booking approval</label><select id="auto-approve" name="auto_approve_bookings" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5"><option value="0" @selected(!old('auto_approve_bookings', $profile->auto_approve_bookings))>I approve each request</option><option value="1" @selected(old('auto_approve_bookings', $profile->auto_approve_bookings))>Automatically confirm bookings</option></select></div>
        <div><label for="show-names" class="mb-2 block text-sm">Queue student names</label><select id="show-names" name="show_student_names" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5"><option value="0" @selected(!old('show_student_names', $profile->show_student_names))>Hide names from other students</option><option value="1" @selected(old('show_student_names', $profile->show_student_names))>Show names to students</option></select></div>
        <p class="text-xs text-slate-400 sm:col-span-3">Duration is copied into newly published availability; existing slots never change. Auto-approval applies to new bookings only. School name-visibility rule: {{ str_replace('_', ' ', $siteSettings->queue_name_visibility) }} (overrides your preference if school-wide).</p>
        <button class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold">Save preferences</button>
    </form>
</section>
@if($profile->consultation_approved)
<section class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-7"><h2 class="text-lg font-semibold">Publish availability</h2><form method="POST" action="{{ route('consultations.availability.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">@csrf
    <div><label class="mb-2 block text-xs font-medium text-slate-300" for="date">Date</label><input id="date" name="date" type="date" min="{{ today()->toDateString() }}" value="{{ old('date') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></div>
    <div><label class="mb-2 block text-xs font-medium text-slate-300" for="starts_at">From</label><input id="starts_at" name="starts_at" type="time" value="{{ old('starts_at') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></div>
    <div><label class="mb-2 block text-xs font-medium text-slate-300" for="ends_at">Until</label><input id="ends_at" name="ends_at" type="time" value="{{ old('ends_at') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></div>
    <div><label class="mb-2 block text-xs font-medium text-slate-300" for="slot_minutes">Appointment length (minutes)</label><input id="slot_minutes" name="slot_minutes" type="number" required min="5" max="240" value="{{ old('slot_minutes', $profile->slot_minutes) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></div>
    <div><label class="mb-2 block text-xs font-medium text-slate-300" for="location">Location</label><input id="location" name="location" value="{{ old('location') }}" maxlength="120" placeholder="Room / office" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></div>
    <div class="flex items-end"><button class="w-full rounded-lg bg-rose-500 px-4 py-2.5 text-sm font-semibold hover:bg-rose-400">Publish times</button></div>
    @if($errors->any())<div class="text-sm text-rose-300 sm:col-span-2 lg:col-span-6">{{ $errors->first() }}</div>@endif
</form></section>
@endif
<section class="mt-10"><h2 class="text-xl font-semibold">Upcoming requests &amp; calendar</h2><p class="mt-1 text-sm text-slate-400">Pending requests are reserved in the queue until you confirm or reject them.</p><div class="mt-4 space-y-3">
    @forelse($appointments as $appointment)
        <article class="flex flex-col justify-between gap-4 rounded-xl border border-slate-800 bg-slate-900 p-5 sm:flex-row sm:items-center"><div><div class="flex flex-wrap items-center gap-2"><p class="font-semibold">{{ $appointment->starts_at->format('D, M j · g:i A') }}</p><span @class(['rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide', 'bg-amber-400/10 text-amber-200' => $appointment->status === 'pending', 'bg-emerald-400/10 text-emerald-200' => $appointment->status === 'confirmed', 'bg-slate-700 text-slate-300' => in_array($appointment->status, ['completed','cancelled'])])>{{ $appointment->status }}</span></div><p class="mt-1 text-sm text-slate-300">{{ $appointment->student->name }} · {{ $appointment->ends_at->format('g:i A') }}@if($appointment->availability->location) · {{ $appointment->availability->location }}@endif</p>@if($appointment->topic)<p class="mt-1 text-sm text-slate-400">{{ $appointment->topic }}</p>@endif</div>
            <div class="flex flex-wrap gap-2">
                @if($appointment->status === 'pending')
                    @if($profile->consultation_approved && $appointment->starts_at->isFuture())<form method="POST" action="{{ route('consultations.appointments.update', $appointment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="confirmed"><button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold">Confirm</button></form>@endif
                    <form method="POST" action="{{ route('consultations.appointments.update', $appointment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="rounded-lg border border-slate-700 px-3 py-2 text-xs">Reject</button></form>
                @elseif($appointment->status === 'confirmed')
                    <form method="POST" action="{{ route('consultations.appointments.update', $appointment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button class="rounded-lg border border-slate-700 px-3 py-2 text-xs">Mark completed</button></form>
                    <form method="POST" action="{{ route('consultations.appointments.update', $appointment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><button class="rounded-lg border border-rose-900 px-3 py-2 text-xs text-rose-300">Cancel booking</button></form>
                @endif
            </div>
        </article>
    @empty<div class="rounded-xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-400">No consultation requests yet. Publish availability to let students book.</div>@endforelse
</div></section>
<section class="mt-10"><h2 class="text-xl font-semibold">Published availability</h2><div class="mt-4 grid gap-3 sm:grid-cols-2">@forelse($availability as $period)<article class="rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="font-medium">{{ $period->date->format('l, F j') }}</p><p class="mt-1 text-sm text-slate-400">{{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($period->ends_at)->format('g:i A') }} · {{ $period->slot_minutes }} minute slots</p>@if($period->location)<p class="mt-1 text-xs text-slate-500">{{ $period->location }}</p>@endif
    <form method="POST" action="{{ route('consultations.availability.cancel', $period) }}" class="mt-4" onsubmit="return confirm('Cancel this availability? All pending and confirmed bookings will be rejected.');">@csrf @method('PATCH')<button class="rounded-lg border border-rose-900 px-3 py-2 text-xs text-rose-300">Cancel availability &amp; reject queue</button></form>
</article>@empty<p class="text-sm text-slate-500">No dates published yet.</p>@endforelse</div></section>
@endsection
