@php
    $monthStart = $month->copy()->startOfMonth();
    $offset = $monthStart->dayOfWeekIso - 1;
    $cellCount = (int) (ceil(($offset + $monthStart->daysInMonth) / 7) * 7);
    $selectedDate = $selectedDay->toDateString();
    $dayAvailability = $calendarAvailability->get($selectedDate, collect());
    $dayAppointments = $calendarAppointments->get($selectedDate, collect());
    $calendarStatuses = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed'];
@endphp
<section id="teacher-month-calendar" class="responsive-calendar mt-8 min-w-0 scroll-mt-5 rounded-2xl border border-slate-800 bg-slate-900 p-2.5 sm:p-6" aria-labelledby="teacher-calendar-heading">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 id="teacher-calendar-heading" class="text-xl font-semibold">My schedule calendar</h2>
            <p class="mt-1 text-xs text-slate-400">Select a date to see your availability and consultations. Times: {{ config('app.timezone') }}.</p>
        </div>
    </div>
    <div class="calendar-workspace mt-5">
    <div class="calendar-month-pane">
    <div class="calendar-toolbar">
        <a href="{{ route('consultations.teacher', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}#teacher-month-calendar" aria-label="Previous month" class="calendar-navigation rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800">←</a>
        <h3 class="min-w-0 text-center text-sm font-semibold sm:text-lg">{{ $month->format('F Y') }}</h3>
        <a href="{{ route('consultations.teacher', ['month' => today()->format('Y-m'), 'day' => today()->toDateString()]) }}#teacher-month-calendar" class="calendar-navigation rounded-lg border border-slate-700 px-2 py-2 text-xs hover:bg-slate-800 sm:text-sm">Today</a>
        <a href="{{ route('consultations.teacher', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}#teacher-month-calendar" aria-label="Next month" class="calendar-navigation rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800">→</a>
    </div>
    <div class="calendar-month-grid grid-cols-7" aria-label="{{ $month->format('F Y') }} dates">
        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)<span class="calendar-weekday">{{ $weekday }}</span>@endforeach
        @foreach(range(0, $cellCount - 1) as $cell)
            @php
                $day = $cell - $offset + 1;
                $inMonth = $day >= 1 && $day <= $monthStart->daysInMonth;
                $date = $inMonth ? $monthStart->copy()->addDays($day - 1)->toDateString() : null;
                $periods = $date ? $calendarAvailability->get($date, collect()) : collect();
                $bookings = $date ? $calendarAppointments->get($date, collect()) : collect();
                $statusCounts = $bookings->countBy('status');
                $dayLabel = $inMonth ? $monthStart->copy()->day($day)->format('F j, Y').', '.$periods->count().' availability windows, '.$bookings->count().' consultations, '.($statusCounts['pending'] ?? 0).' pending, '.($statusCounts['confirmed'] ?? 0).' confirmed, '.($statusCounts['completed'] ?? 0).' completed' : '';
            @endphp
            @if($inMonth)
                <a href="{{ route('consultations.teacher', ['month' => $month->format('Y-m'), 'day' => $date]) }}#teacher-month-calendar"
                   aria-label="{{ $dayLabel }}" title="{{ $dayLabel }}"
                   data-calendar-date="{{ $date }}" data-selected="{{ $date === $selectedDate ? 'true' : 'false' }}"
                   @if($date === today()->toDateString()) aria-current="date" @endif
                   class="calendar-day">
                    <span class="calendar-day-number">{{ $day }}</span>
                    <span class="calendar-event-dots" aria-hidden="true">
                        @if($periods->isNotEmpty())<span class="calendar-event-dot calendar-dot-availability" data-calendar-indicator="availability"></span>@endif
                        @foreach($calendarStatuses as $status => $label)
                            @if(($statusCounts[$status] ?? 0) > 0)<span class="calendar-event-dot calendar-dot-{{ $status }}" data-calendar-indicator="{{ $status }}"></span>@endif
                        @endforeach
                    </span>
                    <span class="calendar-desktop-events" aria-hidden="true">
                        @if($periods->isNotEmpty() || $bookings->isNotEmpty())<span class="calendar-status-counts">
                            @if($periods->isNotEmpty())<span class="calendar-status-count calendar-status-availability" data-calendar-indicator="availability">{{ $periods->count() }} {{ \Illuminate\Support\Str::plural('window', $periods->count()) }}</span>@endif
                            @foreach($calendarStatuses as $status => $label)
                                @if(($statusCounts[$status] ?? 0) > 0)<span class="calendar-status-count calendar-status-{{ $status }}" data-calendar-indicator="{{ $status }}">{{ $statusCounts[$status] }} {{ strtolower($label) }}</span>@endif
                            @endforeach
                        </span>@endif
                        @if($periods->isNotEmpty())<span class="calendar-event-preview bg-sky-400/10 text-sky-200">{{ \Illuminate\Support\Carbon::parse($periods->first()->starts_at)->format('g:i A') }} · Available</span>@endif
                        @if($bookings->isNotEmpty())<span class="calendar-event-preview calendar-status-{{ $bookings->first()->status }}">{{ $bookings->first()->starts_at->format('g:i A') }} · {{ $bookings->first()->student->name }}</span>@endif
                        @php $more = $periods->count() + $bookings->count() - (int) $periods->isNotEmpty() - (int) $bookings->isNotEmpty(); @endphp
                        @if($more > 0)<span class="truncate text-slate-400">+{{ $more }} more</span>@endif
                    </span>
                </a>
            @else<div aria-hidden="true" class="calendar-day-blank"></div>@endif
        @endforeach
    </div>
    <ul class="calendar-legend" aria-label="Calendar indicators">
        <li><span class="calendar-event-dot calendar-dot-availability" aria-hidden="true"></span>Published availability</li>
        @foreach($calendarStatuses as $status => $label)<li><span class="calendar-event-dot calendar-dot-{{ $status }}" aria-hidden="true"></span>{{ $label }}</li>@endforeach
        <li><span class="calendar-legend-today" aria-hidden="true"></span>Today</li>
        <li><span class="calendar-legend-selected" aria-hidden="true"></span>Selected date</li>
    </ul>
    @if($calendarAvailability->isEmpty() && $calendarAppointments->isEmpty())<p class="mt-3 text-xs text-slate-400">No availability or consultations in {{ $month->format('F Y') }}. Indicators appear on dates with schedules.</p>@endif
    </div>
    <div class="calendar-agenda" aria-labelledby="selected-day-heading">
        <h3 id="selected-day-heading" class="text-lg font-semibold">Schedule for {{ $selectedDay->format('l, F j, Y') }}</h3>
        <p class="mt-1 text-xs text-slate-400">Daily agenda · Times: {{ config('app.timezone') }}</p>
        <a href="{{ route('consultations.teacher.requests') }}" class="mt-2 inline-flex min-h-11 items-center text-xs text-sky-300 underline underline-offset-4">Manage consultation requests →</a>
        <div class="calendar-agenda-columns mt-4">
            <div class="min-w-0">
                <h4 class="text-sm font-semibold text-sky-300">Availability windows</h4>
                <div class="calendar-agenda-list">@forelse($dayAvailability as $period)
                    <article class="rounded-xl border border-slate-800 bg-slate-950 p-4">
                        <p class="text-sm font-semibold">{{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($period->ends_at)->format('g:i A') }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $period->slot_minutes }} minute slots @if($period->location) · {{ $period->location }} @endif</p>
                    </article>
                @empty<p class="rounded-lg border border-dashed border-slate-700 p-4 text-sm text-slate-500">No availability published for this date.</p>@endforelse</div>
            </div>
            <div class="min-w-0">
                <h4 class="text-sm font-semibold text-emerald-300">Consultations in time order</h4>
                <div class="calendar-agenda-list">@forelse($dayAppointments as $booking)
                    <article class="rounded-xl border border-slate-800 bg-slate-950 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold">{{ $booking->starts_at->format('g:i A') }}–{{ $booking->ends_at->format('g:i A') }}</p>
                            <span @class(['rounded-full px-2.5 py-1 text-[10px] font-semibold capitalize', 'bg-amber-400/10 text-amber-200' => $booking->status === 'pending', 'bg-emerald-400/10 text-emerald-200' => $booking->status === 'confirmed', 'bg-slate-800 text-slate-300' => $booking->status === 'completed'])>{{ $booking->status }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-300">{{ $booking->student->name }}</p>
                        @if($booking->availability->location)<p class="mt-1 text-xs text-slate-400">{{ $booking->availability->location }}</p>@endif
                        @if($booking->topic)<p class="mt-2 break-words text-xs text-slate-400">{{ $booking->topic }}</p>@endif
                    </article>
                @empty<p class="rounded-lg border border-dashed border-slate-700 p-4 text-sm text-slate-500">No consultations scheduled for this date.</p>@endforelse</div>
            </div>
        </div>
    </div>
    </div>
    <p class="mt-4 text-xs text-slate-500">Cancelled availability and cancelled/rejected bookings are not shown.</p>
</section>
