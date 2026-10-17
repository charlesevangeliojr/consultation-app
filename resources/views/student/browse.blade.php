@extends('layouts.consultations')
@section('title', 'Book a consultation')
@section('content')
@include('student.partials.page-header', ['heading' => 'Find a consultation time', 'description' => 'Choose a teacher, explore their availability, and find the right time for you.'])
<form method="GET" class="student-card student-filter"><label for="teacher" class="mb-2 block text-xs font-medium text-slate-300">Choose your teacher</label><div class="flex max-w-2xl gap-3"><select id="teacher" name="teacher" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-4 py-3">@forelse($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($selectedTeacher?->id === $teacher->id)>{{ $teacher->name }}@if($teacher->role) · {{ $teacher->role->name }}@endif</option>@empty<option>No teacher accounts found</option>@endforelse</select><button class="student-primary-button">View</button></div></form>
@error('starts_at')<p class="mt-4 rounded-lg bg-rose-950/40 p-3 text-sm text-rose-200">{{ $message }}</p>@enderror
@error('ends_at')<p role="alert" class="mt-4 rounded-lg bg-rose-950/40 p-3 text-sm text-rose-200">{{ $message }}</p>@enderror
@error('topic')<p class="mt-4 rounded-lg bg-rose-950/40 p-3 text-sm text-rose-200">{{ $message }}</p>@enderror
@error('date')<p role="alert" class="mt-4 rounded-lg bg-rose-950/40 p-3 text-sm text-rose-200">{{ $message }}</p>@enderror
@if($selectedTeacher)
<section class="student-browse-grid" aria-label="Teacher schedule and available sessions">
    <div>
    <div class="student-card student-teacher-summary">
        <div class="student-teacher-identity">
            @if($selectedTeacher->teacher->profile_photo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($selectedTeacher->teacher->profile_photo_path) }}" alt="{{ $selectedTeacher->name }} profile picture">@else<span aria-hidden="true">{{ mb_substr($selectedTeacher->name, 0, 1) }}</span>@endif
            <div><p class="student-eyebrow">YOUR TEACHER</p><h2 class="mt-1">{{ $selectedTeacher->name }}</h2></div>
        </div>
        <p class="mt-3 text-xs leading-6 text-slate-400">{{ $selectedTeacher->teacher->subjects->pluck('name')->join(', ') }}</p>
        <span class="student-status mt-3 {{ $selectedTeacher->teacher->auto_approve_bookings ? 'student-status-confirmed' : 'student-status-pending' }}">{{ $selectedTeacher->teacher->auto_approve_bookings ? 'Automatic confirmation' : 'Teacher approval required' }}</span>
        <p class="mt-3 text-xs leading-6 text-slate-400">{{ $selectedTeacher->teacher->auto_approve_bookings ? 'Your chosen time is automatically confirmed when booked.' : 'Requests reserve your chosen time while awaiting teacher approval.' }}</p>
        <p class="mt-2 text-xs font-medium text-sky-300">Maximum {{ $selectedTeacher->teacher->max_consultation_minutes }} minutes per consultation</p>
        @if($selectedTeacher->teacher->consultation_offerings)<div class="mt-4 border-t border-slate-700 pt-4"><h3 class="text-xs font-semibold text-sky-300">What I can help you with</h3><p class="mt-2 whitespace-pre-line break-words text-sm leading-7 text-slate-300 [overflow-wrap:anywhere]">{{ $selectedTeacher->teacher->consultation_offerings }}</p></div>@endif
    </div>
    @include('student.teacher-calendar')
    <section class="student-card mt-5 p-5" aria-labelledby="proposed-time-heading">
        <h2 id="proposed-time-heading" class="text-lg font-semibold">Request another time</h2>
        <p class="mt-2 text-xs leading-6 text-slate-400">No suitable availability? Propose a date and time, even if no sessions are published. Teacher approval is always required. Unanswered requests are automatically rejected when the start time arrives or another accepted consultation takes the time.</p>
        <form method="POST" action="{{ route('consultations.request-time', $selectedTeacher) }}" class="student-range-form" data-booking-range data-booking-maximum="{{ $selectedTeacher->teacher->max_consultation_minutes }}" data-booking-busy="[]">
            @csrf
            <input type="hidden" name="proposed_time" value="1">
            <div class="student-range-reason"><label for="proposed-date">Requested date</label><input id="proposed-date" name="date" type="date" min="{{ today()->toDateString() }}" value="{{ old('proposed_time') ? old('date') : '' }}" required></div>
            <div><label for="proposed-start">Requested start time</label><input id="proposed-start" name="starts_at" type="time" min="00:00" max="23:58" step="60" value="{{ old('proposed_time') ? old('starts_at') : '' }}" required></div>
            <div><label for="proposed-end">Requested end time</label><input id="proposed-end" name="ends_at" type="time" min="00:01" max="23:59" step="60" value="{{ old('proposed_time') ? old('ends_at') : '' }}" required></div>
            <p class="student-booking-duration" data-booking-duration aria-live="polite">Choose a start and end time. Maximum {{ $selectedTeacher->teacher->max_consultation_minutes }} minutes.</p>
            <div class="student-range-reason"><label for="proposed-topic">Reason for proposed consultation</label><textarea id="proposed-topic" name="topic" rows="3" maxlength="500" required>{{ old('proposed_time') ? old('topic') : '' }}</textarea></div>
            <button type="submit" class="student-primary-button">Send time request</button>
        </form>
    </section>
    </div>
    <div><div class="mb-4"><h2 class="text-lg font-semibold">Available sessions</h2><p class="mt-1 text-xs leading-6 text-slate-400">Choose your start and end time, up to {{ $selectedTeacher->teacher->max_consultation_minutes }} minutes. You can consult multiple teachers, but your bookings must not overlap. Times: {{ config('app.timezone') }}.</p></div>
    <div class="student-session-list">
        @forelse($availability as $period)
            @php
                $periodStart = \Illuminate\Support\Carbon::parse($period->date->toDateString().' '.$period->starts_at);
                $periodEnd = \Illuminate\Support\Carbon::parse($period->date->toDateString().' '.$period->ends_at);
                $reserved = $teacherBookings
                    ->concat($studentBookings)->filter(fn($booking) => $booking->starts_at->lt($periodEnd) && $booking->ends_at->gt($periodStart))->sortBy('starts_at');
                $busy = $reserved->map(fn($booking) => ['start' => $booking->starts_at->format('H:i'), 'end' => $booking->ends_at->format('H:i')])->values()->all();
                $freeRanges = [];
                $cursor = $periodStart->gt(now()) ? $periodStart->copy() : now()->startOfMinute()->addMinute();
                foreach ($reserved as $reservedBooking) {
                    if ($reservedBooking->starts_at->gt($cursor)) $freeRanges[] = ['start' => $cursor->copy(), 'end' => $reservedBooking->starts_at->copy()->min($periodEnd)];
                    if ($reservedBooking->ends_at->gt($cursor)) $cursor = $reservedBooking->ends_at->copy();
                }
                if ($cursor->lt($periodEnd)) $freeRanges[] = ['start' => $cursor->copy(), 'end' => $periodEnd->copy()];
                $isRetry = (int) old('booking_availability') === $period->id;
                $oldTime = function ($key) use ($isRetry) { return $isRetry && is_string(old($key)) && preg_match('/(?:^| )(\d{2}:\d{2})(?::\d{2})?$/', old($key), $matches) ? $matches[1] : ''; };
            @endphp
            <article id="availability-{{ $period->date->toDateString() }}{{ !$loop->first && $availability[$loop->index - 1]->date->isSameDay($period->date) ? '-'.$period->id : '' }}" data-schedule-date="{{ $period->date->toDateString() }}" class="student-card p-5"><div class="student-session-heading"><div><h3 class="text-sm font-semibold">{{ $period->date->format('l, F j, Y') }}</h3><p class="mt-2 text-xs text-slate-400">{{ $periodStart->format('g:i A') }}–{{ $periodEnd->format('g:i A') }}</p>@if($period->location)<p class="mt-2 text-xs text-slate-400">{{ $period->location }}</p>@endif</div><span class="student-status">Up to {{ $selectedTeacher->teacher->max_consultation_minutes }} min</span></div>
                @if(count($freeRanges))
                    <p class="mt-4 text-xs font-medium text-sky-300">Available for you</p>
                    <div class="student-free-ranges">@foreach($freeRanges as $range)<span>{{ $range['start']->format('g:i A') }}–{{ $range['end']->format('g:i A') }}</span>@endforeach</div>
                    <form method="POST" action="{{ route('consultations.book', $period) }}" class="student-range-form" data-booking-range data-booking-maximum="{{ $selectedTeacher->teacher->max_consultation_minutes }}" data-booking-busy="{{ json_encode($busy) }}">
                        @csrf<input type="hidden" name="booking_availability" value="{{ $period->id }}">
                        <div><label for="booking-start-{{ $period->id }}">Start time</label><input id="booking-start-{{ $period->id }}" name="starts_at" type="time" required step="60" min="{{ $freeRanges[0]['start']->format('H:i') }}" max="{{ $periodEnd->copy()->subMinute()->format('H:i') }}" value="{{ $oldTime('starts_at') }}" aria-describedby="booking-help-{{ $period->id }}"></div>
                        <div><label for="booking-end-{{ $period->id }}">End time</label><input id="booking-end-{{ $period->id }}" name="ends_at" type="time" required step="60" min="{{ $freeRanges[0]['start']->copy()->addMinute()->format('H:i') }}" max="{{ $periodEnd->format('H:i') }}" value="{{ $oldTime('ends_at') }}" aria-describedby="booking-help-{{ $period->id }}"></div>
                        <p class="student-booking-duration" id="booking-help-{{ $period->id }}" data-booking-duration aria-live="polite">Choose a start and end time. Maximum {{ $selectedTeacher->teacher->max_consultation_minutes }} minutes.</p>
                        <div class="student-range-reason"><label for="booking-topic-{{ $period->id }}">Consultation reason</label><textarea id="booking-topic-{{ $period->id }}" name="topic" rows="2" required maxlength="500" placeholder="What would you like help with?">{{ $isRetry ? old('topic') : '' }}</textarea></div>
                        <button type="submit" class="student-primary-button">{{ $selectedTeacher->teacher->auto_approve_bookings ? 'Book consultation' : 'Request consultation' }} <span aria-hidden="true">→</span></button>
                    </form>
                @else<p class="student-empty-state mt-4">No available time remains for you in this window. Choose another date or teacher.</p>@endif
                <div class="student-queue">
                    <h4 class="text-sm font-semibold">Consultation queue (by appointment time)</h4>
                    <ol class="mt-3 space-y-2 text-sm">
                        @forelse($period->appointments->whereIn('status', ['pending', 'confirmed'])->sortBy('starts_at') as $booking)
                            <li class="flex flex-wrap items-center gap-2 rounded-lg bg-slate-950 px-3 py-2">
                                <span class="text-slate-500">{{ $loop->iteration }}.</span>
                                <span>{{ $booking->starts_at->format('g:i A') }}–{{ $booking->ends_at->format('g:i A') }}</span>
                                <span class="text-slate-300">{{ $booking->student_id === auth()->id() ? 'You' : ($showStudentNames ? $booking->student->name : 'Student (name hidden)') }}</span>
                                <span class="text-xs capitalize text-slate-500">{{ $booking->status }}</span>
                            </li>
                        @empty<li class="text-xs text-slate-500">No students queued for this session yet.</li>@endforelse
                    </ol>
                    @if(! $showStudentNames)<p class="mt-3 text-xs text-slate-500">Other students’ names are hidden by teacher or school policy.</p>@endif
                </div>
            </article>
        @empty<div class="student-card student-empty-state">This teacher has not published any upcoming consultation times yet.</div>@endforelse
    </div>
    </div>
</section>
@else<div class="mt-8 rounded-2xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-400">No teacher accounts found. Ask an administrator to create a user with the Teacher role.</div>@endif
@endsection
