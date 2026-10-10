@extends('layouts.consultations')

@section('title', 'Student Dashboard')

@section('content')
    @php
        $monthStart = $month->copy()->startOfMonth();
        $dayOffset = $monthStart->dayOfWeekIso - 1;
        $monthDays = $monthStart->daysInMonth;
        $appointmentDateList = $appointmentDates->all();
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[.2em] text-rose-400">Student portal</p>
            <h1 class="mt-2 text-3xl font-bold">Dashboard</h1>
            <p class="mt-2 text-slate-400">Welcome back, {{ auth()->user()->name }}. Here’s your consultation schedule.</p>
        </div>
        <a href="{{ route('consultations.browse') }}" class="rounded-lg bg-rose-500 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-400">Browse calendar</a>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
        <section class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Your bookings</p>
                    <h2 class="mt-1 text-xl font-semibold">{{ $month->format('F Y') }}</h2>
                </div>
                <span class="rounded-full bg-rose-400/10 px-3 py-1 text-xs font-medium text-rose-200">{{ $appointmentDates->count() }} {{ \Illuminate\Support\Str::plural('day', $appointmentDates->count()) }}</span>
            </div>
            <div class="mt-5 grid grid-cols-7 gap-1 text-center text-xs">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                    <span class="py-2 text-[10px] font-medium uppercase text-slate-500">{{ $weekday }}</span>
                @endforeach
                @foreach (range(0, 41) as $cell)
                    @php
                        $day = $cell - $dayOffset + 1;
                        $inMonth = $day >= 1 && $day <= $monthDays;
                        $date = $inMonth ? $monthStart->copy()->addDays($day - 1)->toDateString() : null;
                        $hasAppointment = $date && in_array($date, $appointmentDateList, true);
                        $isToday = $date === today()->toDateString();
                    @endphp
                    <div class="relative grid aspect-square place-items-center rounded-lg {{ $isToday ? 'bg-rose-500 font-semibold text-white' : ($inMonth ? 'text-slate-300' : 'text-slate-800') }}">
                        {{ $inMonth ? $day : '' }}
                        @if ($hasAppointment)
                            <span class="absolute bottom-1 h-1 w-1 rounded-full {{ $isToday ? 'bg-white' : 'bg-rose-400' }}"></span>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-slate-500"><span class="mr-1 inline-block h-2 w-2 rounded-full bg-rose-400"></span>Scheduled consultation</p>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Coming up</p>
                    <h2 class="mt-1 text-xl font-semibold">Upcoming consultations</h2>
                </div>
                <a href="{{ route('consultations.browse') }}" class="text-xs font-semibold text-rose-300 hover:text-rose-200">View calendar →</a>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($appointments as $appointment)
                    <article class="rounded-xl border border-slate-800 bg-slate-950 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ $appointment->teacher->name }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $appointment->starts_at->format('D, M j · g:i A') }}</p>
                            </div>
                            <span class="rounded-full bg-slate-800 px-2.5 py-1 text-[10px] font-medium capitalize text-slate-300">{{ $appointment->status }}</span>
                        </div>
                        @if ($appointment->topic)
                            <p class="mt-3 border-t border-slate-800 pt-3 text-xs text-slate-400">{{ $appointment->topic }}</p>
                        @endif
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-700 p-6 text-center">
                        <p class="text-sm font-medium text-slate-300">No upcoming consultations</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Browse the calendar to find an available teacher and request a time.</p>
                        <a href="{{ route('consultations.browse') }}" class="mt-4 inline-block text-sm font-semibold text-rose-300 hover:text-rose-200">Browse calendar →</a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
