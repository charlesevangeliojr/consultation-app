@extends('layouts.consultations')
@section('title', 'Consultation settings')
@section('content')
    @include('teacher.partials.page-header', ['heading' => 'Consultation settings', 'description' => 'Set defaults for new availability, booking approval, and student queue privacy.'])
    <section id="teacher-settings" class="mt-6 max-w-3xl rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-6" aria-label="Consultation preferences">
        <form method="POST" action="{{ route('consultations.teacher.settings') }}" class="space-y-6">
            @csrf @method('PUT')
            <div>
                <label for="default-slot-minutes" class="mb-2 block text-sm font-medium">Default slot duration (minutes)</label>
                <input id="default-slot-minutes" name="slot_minutes" type="number" required min="5" max="240" value="{{ old('slot_minutes', $profile->slot_minutes) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3">
                <p class="mt-2 text-xs leading-5 text-slate-400">Between 5 and 240 minutes. This default is copied into new availability windows; existing slots never change.</p>
            </div>
            <div>
                <label for="auto-approve" class="mb-2 block text-sm font-medium">Booking approval</label>
                <select id="auto-approve" name="auto_approve_bookings" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3">
                    <option value="0" @selected(!old('auto_approve_bookings', $profile->auto_approve_bookings))>I approve each request</option>
                    <option value="1" @selected(old('auto_approve_bookings', $profile->auto_approve_bookings))>Automatically confirm bookings</option>
                </select>
                <p class="mt-2 text-xs leading-5 text-slate-400">Changes apply to new bookings only. Pending requests remain pending until you review them.</p>
            </div>
            <div>
                <label for="show-names" class="mb-2 block text-sm font-medium">Queue student names</label>
                <select id="show-names" name="show_student_names" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3">
                    <option value="0" @selected(!old('show_student_names', $profile->show_student_names))>Hide names from other students</option>
                    <option value="1" @selected(old('show_student_names', $profile->show_student_names))>Show names to students</option>
                </select>
                <p class="mt-2 text-xs leading-5 text-slate-400">Consultation reasons are always private from other students.</p>
            </div>
            <div class="rounded-xl border border-slate-700 bg-slate-950 p-4 text-sm text-slate-300">School name-visibility rule: <strong>{{ str_replace('_', ' ', $siteSettings->queue_name_visibility) }}</strong>.<p class="mt-1 text-xs leading-5 text-slate-400">School-wide hide/show rules override your preference. Teachers and authorized administrators can still see names.</p></div>
            <button type="submit" class="rounded-lg bg-rose-500 px-5 py-3 text-sm font-semibold hover:bg-rose-400">Save preferences</button>
        </form>
    </section>
@endsection
