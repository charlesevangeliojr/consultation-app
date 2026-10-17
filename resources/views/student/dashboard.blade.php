@extends('layouts.consultations')

@section('title', 'Student Dashboard')

@section('content')
    @php
        $monthStart = $month->copy()->startOfMonth();
        $dayOffset = $monthStart->dayOfWeekIso - 1;
        $monthDays = $monthStart->daysInMonth;
        $appointmentDateList = $appointmentDates->all();
    @endphp

    @include('student.partials.page-header', ['heading' => 'Dashboard', 'description' => 'Welcome back, '.auth()->user()->name.'. Here’s your consultation schedule.', 'actionUrl' => route('consultations.browse'), 'actionLabel' => 'Browse calendar'])
    <dl class="student-stat-grid" aria-label="Your consultation summary">
        @foreach([['calendar', 'Scheduled days · '.$month->format('M Y'), 'Days', $appointmentDates->count(), 'sky'], ['users', 'Next consultations', 'Upcoming', $appointments->count(), 'green'], ['monitor', 'Booking history & updates', 'History', $history->total(), 'amber']] as [$icon, $label, $mobileLabel, $count, $tone])
            <div class="student-card student-stat"><span class="student-stat-icon" aria-hidden="true">@include('partials.navigation-icon', ['icon' => $icon])</span><div><dt><span class="sm:hidden">{{ $mobileLabel }}</span><span class="hidden sm:inline">{{ $label }}</span></dt><dd data-stat-tone="{{ $tone }}">{{ $count }}</dd></div></div>
        @endforeach
    </dl>

    <div class="student-dashboard-grid">
        <section class="student-card responsive-calendar calendar-mini" aria-label="Your bookings calendar">
            <div class="student-card-heading">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Your bookings</p>
                    <h2 class="mt-1 text-xl font-semibold">{{ $month->format('F Y') }}</h2>
                </div>
                <span class="rounded-full bg-rose-400/10 px-3 py-1 text-xs font-medium text-rose-200">{{ $appointmentDates->count() }} {{ \Illuminate\Support\Str::plural('day', $appointmentDates->count()) }}</span>
            </div>
            <div class="calendar-month-grid m-4 grid-cols-7 text-center">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                    <span class="py-2 text-[10px] font-medium uppercase text-slate-500">{{ $weekday }}</span>
                @endforeach
                @foreach (range(0, (int) (ceil(($dayOffset + $monthDays) / 7) * 7) - 1) as $cell)
                    @php
                        $day = $cell - $dayOffset + 1;
                        $inMonth = $day >= 1 && $day <= $monthDays;
                        $date = $inMonth ? $monthStart->copy()->addDays($day - 1)->toDateString() : null;
                        $hasAppointment = $date && in_array($date, $appointmentDateList, true);
                        $isToday = $date === today()->toDateString();
                    @endphp
                    <div class="{{ $inMonth ? 'calendar-day' : 'calendar-day-blank' }}" @if($isToday) aria-current="date" @endif @unless($inMonth) aria-hidden="true" @endunless>
                        @if($inMonth)<span class="calendar-day-number">{{ $day }}</span>@endif
                        <span class="calendar-event-dots" aria-hidden="true">
                        @if ($hasAppointment)
                            <span class="calendar-event-dot calendar-dot-confirmed"></span>
                        @endif
                        </span>
                    </div>
                @endforeach
            </div>
            <p class="px-5 pb-5 text-xs text-slate-400"><span class="mr-1 inline-block h-2 w-2 rounded-full bg-emerald-400"></span>Scheduled consultation · Times: {{ config('app.timezone') }}</p>
        </section>

        <section class="student-card">
            <div class="student-card-heading">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Coming up</p>
                    <h2 class="mt-1 text-xl font-semibold">Upcoming consultations</h2>
                </div>
                <a href="{{ route('consultations.browse') }}" class="text-xs font-semibold text-rose-300 hover:text-rose-200">View calendar →</a>
            </div>
            <div class="student-booking-list">
                @forelse ($appointments as $appointment)
                    <article class="student-booking">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ $appointment->teacher->name }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $appointment->starts_at->format('D, M j · g:i A') }}–{{ $appointment->ends_at->format('g:i A') }} · {{ (int) $appointment->starts_at->diffInMinutes($appointment->ends_at) }} min</p>
                            </div>
                             <span class="student-status student-status-{{ $appointment->status }}">{{ $appointment->status }}</span>
                        </div>
                        @if ($appointment->topic)
                            <p class="mt-3 border-t border-slate-800 pt-3 text-xs text-slate-400">{{ $appointment->topic }}</p>
                        @endif
                        @if($appointment->is_proposed)<p class="mt-2 text-xs text-sky-300">Proposed time · {{ $appointment->status === 'pending' ? 'Awaiting teacher approval' : 'Teacher accepted' }}</p>@endif
                        @if(in_array($appointment->status, ['pending', 'confirmed'], true) && $appointment->starts_at->isFuture())
                            <form method="POST" action="{{ route('consultations.appointments.cancel', $appointment) }}" class="mt-3" onsubmit="return confirm('Cancel this consultation? You can book another available time afterward.');">@csrf @method('PATCH')<button class="min-h-11 rounded-lg border border-rose-900 px-3 py-2 text-xs text-rose-300">Cancel booking</button></form>
                        @endif
                    </article>
                @empty
                    <div class="student-empty-state">
                        <p class="text-sm font-medium text-slate-300">No upcoming consultations</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Browse the calendar to find an available teacher and request a time.</p>
                        <a href="{{ route('consultations.browse') }}" class="mt-4 inline-block text-sm font-semibold text-rose-300 hover:text-rose-200">Browse calendar →</a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
    <section class="student-card mt-6"><div class="student-card-heading"><div><h2>Booking history &amp; updates</h2><p>Cancelled bookings are not rescheduled. Book again from the teacher calendar.</p></div></div>
        @error('booking')<p role="alert" class="mt-3 text-sm text-rose-300">{{ $message }}</p>@enderror
        <div class="student-booking-list">@forelse($history as $booking)<article class="student-booking"><div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm font-medium">{{ $booking->teacher->name }}</p><span class="student-status student-status-{{ $booking->status }}">{{ $booking->status }}</span></div><p class="mt-2 text-xs text-slate-400">{{ $booking->starts_at->format('M j, Y · g:i A') }}–{{ $booking->ends_at->format('g:i A') }}</p>@if($booking->availability?->cancelled_at && $booking->status === 'rejected')<p class="mt-2 text-sm text-rose-300">The teacher cancelled this availability. Pending and confirmed bookings were automatically rejected.</p>@endif @if($booking->rejectionMessage())<p class="mt-2 text-xs text-rose-300">{{ $booking->rejectionMessage() }}</p>@endif<p class="mt-2 text-xs text-slate-500">{{ $booking->topic }}</p></article>@empty<div class="student-empty-state">No previous bookings or cancellations.</div>@endforelse</div>@if($history->hasPages())<div class="px-5 pb-5">{{ $history->links() }}</div>@endif
    </section>
@endsection
