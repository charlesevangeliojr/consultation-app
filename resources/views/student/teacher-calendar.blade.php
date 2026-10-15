@php
    $calendarStart = $month->copy()->startOfMonth();
    $offset = $calendarStart->dayOfWeekIso - 1;
    $cellCount = (int) (ceil(($offset + $calendarStart->daysInMonth) / 7) * 7);
    $dates = $availability->pluck('date')->map(fn ($date) => $date->toDateString())->unique()->all();
@endphp
<section id="student-teacher-calendar" class="responsive-calendar calendar-mini mt-6 min-w-0 max-w-2xl rounded-2xl border border-slate-800 bg-slate-900 p-3 sm:p-5" aria-label="Teacher availability calendar">
    <h3 class="mb-3 text-sm font-semibold">Choose a consultation date</h3>
    <div class="calendar-toolbar">
        <a href="{{ route('consultations.browse', ['teacher' => $selectedTeacher->id, 'month' => $month->copy()->subMonth()->format('Y-m')]) }}#student-teacher-calendar" aria-label="Previous month" class="calendar-navigation rounded-lg border border-slate-700">←</a>
        <h3 class="min-w-0 text-center text-sm font-semibold sm:text-base">{{ $month->format('F Y') }}</h3>
        <a href="{{ route('consultations.browse', ['teacher' => $selectedTeacher->id, 'month' => today()->format('Y-m')]) }}#student-teacher-calendar" class="calendar-navigation rounded-lg border border-slate-700 px-2 text-xs">Today</a>
        <a href="{{ route('consultations.browse', ['teacher' => $selectedTeacher->id, 'month' => $month->copy()->addMonth()->format('Y-m')]) }}#student-teacher-calendar" aria-label="Next month" class="calendar-navigation rounded-lg border border-slate-700">→</a>
    </div>
    <div class="calendar-month-grid grid-cols-7">
        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)<span class="calendar-weekday">{{ $weekday }}</span>@endforeach
        @foreach(range(0, $cellCount - 1) as $cell)
            @php
                $day = $cell - $offset + 1;
                $inMonth = $day >= 1 && $day <= $calendarStart->daysInMonth;
                $date = $inMonth ? $calendarStart->copy()->addDays($day - 1)->toDateString() : null;
                $published = $date && in_array($date, $dates, true);
            @endphp
            @if($published)<a href="#availability-{{ $date }}" class="calendar-day" data-calendar-date="{{ $date }}" aria-label="{{ $calendarStart->copy()->day($day)->format('F j, Y') }}, published availability" @if($date === today()->toDateString()) aria-current="date" @endif><span class="calendar-day-number">{{ $day }}</span><span class="calendar-event-dots" aria-hidden="true"><span class="calendar-event-dot calendar-dot-availability"></span></span></a>
            @elseif($inMonth)<div class="calendar-day opacity-40" @if($date === today()->toDateString()) aria-current="date" @endif><span class="calendar-day-number">{{ $day }}</span><span class="calendar-event-dots" aria-hidden="true"></span></div>
            @else<div class="calendar-day-blank" aria-hidden="true"></div>@endif
        @endforeach
    </div>
    <p class="mt-4 text-xs text-slate-400"><span class="text-sky-300">●</span> Dates with a dot have published sessions. Tap a date to view its slots and queue below. Times use {{ config('app.timezone') }}.</p>
</section>
