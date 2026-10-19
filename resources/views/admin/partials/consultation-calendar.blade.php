@php
    $offset = $month->dayOfWeekIso - 1;
    $cellCount = (int) (ceil(($offset + $month->daysInMonth) / 7) * 7);
@endphp
<section class="admin-monitor-calendar responsive-calendar calendar-mini" aria-labelledby="monitor-calendar-title" id="admin-consultation-calendar">
    <div class="admin-monitor-heading"><div><p class="text-xs text-slate-400">CONSULTATION CALENDAR</p><h3 id="monitor-calendar-title" class="mt-2 text-xl font-semibold">{{ $month->format('F Y') }}</h3></div><p class="text-xs text-slate-400">Select a date to view its records.</p></div>
    <div class="calendar-toolbar">
        <a href="{{ route('admin.consultations', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" aria-label="Previous month" class="calendar-navigation rounded-lg border border-slate-700">←</a>
        <span class="text-center text-xs text-slate-400">{{ $bookingDates->flatten(1)->sum('total') }} bookings this month</span>
        <a href="{{ route('admin.consultations', ['month' => today()->format('Y-m'), 'day' => today()->toDateString()]) }}" class="calendar-navigation rounded-lg border border-slate-700 px-2 text-xs">Today</a>
        <a href="{{ route('admin.consultations', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" aria-label="Next month" class="calendar-navigation rounded-lg border border-slate-700">→</a>
    </div>
    <div class="calendar-month-grid">
        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)<span class="calendar-weekday">{{ $weekday }}</span>@endforeach
        @for($cell = 0; $cell < $cellCount; $cell++)
            @php $number = $cell - $offset + 1; @endphp
            @if($number >= 1 && $number <= $month->daysInMonth)
                @php
                    $date = $month->copy()->day($number);
                    $key = $date->toDateString();
                    $counts = ($bookingDates[$key] ?? collect())->pluck('total', 'status');
                    $published = $availabilityDates[$key] ?? 0;
                    $summary = $date->format('F j, Y').', '.$counts->sum().' bookings, '.$published.' availability windows';
                @endphp
                <a class="calendar-day {{ $date->isSameDay($selectedDay) ? 'admin-monitor-selected' : '' }}" data-monitor-date="{{ $key }}" href="{{ route('admin.consultations', ['month' => $month->format('Y-m'), 'day' => $key]) }}" aria-label="{{ $summary }}" title="{{ $summary }}" @if($date->isToday()) aria-current="date" @endif>
                    <span class="calendar-day-number">{{ $number }}</span>
                    <span class="calendar-event-dots" aria-hidden="true">
                        @if($published)<span class="calendar-event-dot calendar-dot-availability"></span>@endif
                        @if($counts->sum())<span class="calendar-event-dot calendar-dot-consultation"></span>@endif
                        @if($counts['pending'] ?? 0)<span class="calendar-event-dot calendar-dot-pending"></span>@endif
                    </span>
                    @if($date->isSameDay($selectedDay))<span class="sr-only">Selected date</span>@endif
                </a>
            @else<div class="calendar-day-blank" aria-hidden="true"></div>@endif
        @endfor
    </div>
    <ul class="calendar-legend"><li><span class="calendar-event-dot calendar-dot-availability"></span>Published availability</li><li><span class="calendar-event-dot calendar-dot-consultation"></span>Booking records (all statuses)</li><li><span class="calendar-event-dot calendar-dot-pending"></span>Pending review</li></ul>
    <p class="mt-3 text-xs text-slate-400">Times: {{ config('app.timezone') }}. Calendar counts include proposed requests and booking history.</p>
</section>
<div class="admin-monitor-day"><p class="text-xs text-slate-400">SELECTED DATE</p><h3 class="mt-2 text-lg font-semibold">{{ $selectedDay->format('l, F j, Y') }}</h3><p class="mt-2 text-xs text-slate-400">{{ $appointments->total() }} bookings · {{ $availability->total() }} availability windows</p></div>
