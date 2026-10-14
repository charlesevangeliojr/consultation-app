@php
    $calendarStart = $month->copy()->startOfMonth();
    $offset = $calendarStart->dayOfWeekIso - 1;
    $dates = $availability->pluck('date')->map(fn ($date) => $date->toDateString())->unique()->all();
@endphp
<section class="mt-6 max-w-2xl rounded-2xl border border-slate-800 bg-slate-900 p-5">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('consultations.browse', ['teacher' => $selectedTeacher->id, 'month' => $month->copy()->subMonth()->format('Y-m')]) }}" aria-label="Previous month" class="rounded-lg border border-slate-700 px-3 py-2">←</a>
        <h3 class="font-semibold">{{ $month->format('F Y') }}</h3>
        <a href="{{ route('consultations.browse', ['teacher' => $selectedTeacher->id, 'month' => $month->copy()->addMonth()->format('Y-m')]) }}" aria-label="Next month" class="rounded-lg border border-slate-700 px-3 py-2">→</a>
    </div>
    <div class="mt-4 grid grid-cols-7 gap-1 text-center text-xs">
        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)<span class="py-2 text-slate-500">{{ $weekday }}</span>@endforeach
        @foreach(range(0, 41) as $cell)
            @php
                $day = $cell - $offset + 1;
                $inMonth = $day >= 1 && $day <= $calendarStart->daysInMonth;
                $date = $inMonth ? $calendarStart->copy()->addDays($day - 1)->toDateString() : null;
                $published = $date && in_array($date, $dates, true);
            @endphp
            @if($published)<a href="#availability-{{ $date }}" class="grid aspect-square place-items-center rounded-lg bg-rose-500/20 font-semibold text-rose-200 hover:bg-rose-500/40">{{ $day }}</a>
            @else<div class="grid aspect-square place-items-center rounded-lg {{ $inMonth ? 'text-slate-400' : 'text-slate-800' }}">{{ $inMonth ? $day : '' }}</div>@endif
        @endforeach
    </div>
    <p class="mt-4 text-xs text-slate-400">Highlighted dates have published consultation sessions. Select a date to view its slots and queue below. Times use {{ config('app.timezone') }}.</p>
</section>
