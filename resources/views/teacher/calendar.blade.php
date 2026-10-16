@extends('layouts.consultations')
@section('title', 'Teacher calendar')
@section('content')
    @include('teacher.partials.page-header', [
        'heading' => 'Consultation calendar',
        'description' => 'Your schedule at a glance. Choose a date to review availability and consultations in the daily agenda.',
        'actionUrl' => route('consultations.teacher.availability'),
        'actionLabel' => 'Manage availability',
        'compactMobile' => true,
    ])
    <dl class="teacher-summary mt-6 grid gap-3 sm:grid-cols-3" aria-label="Consultation summary for {{ $month->format('M Y') }}">
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><dt class="text-xs text-slate-400"><span class="sm:hidden">Availability</span><span class="hidden sm:inline">Availability windows · {{ $month->format('M Y') }}</span></dt><dd class="mt-2 text-2xl font-semibold text-sky-300">{{ $calendarAvailability->flatten(1)->count() }}</dd></div>
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><dt class="text-xs text-slate-400"><span class="sm:hidden">Scheduled</span><span class="hidden sm:inline">Scheduled consultations · {{ $month->format('M Y') }}</span></dt><dd class="mt-2 text-2xl font-semibold text-emerald-300">{{ $calendarAppointments->flatten(1)->count() }}</dd></div>
        <div class="relative rounded-xl border border-slate-800 bg-slate-900 p-4"><dt class="text-xs text-slate-400"><span class="sm:hidden">Pending</span><span class="hidden sm:inline">Pending requests · {{ $month->format('M Y') }}</span></dt><dd class="mt-2 text-2xl font-semibold text-amber-300">{{ $calendarAppointments->flatten(1)->where('status', 'pending')->count() }}</dd><a href="{{ route('consultations.teacher.requests', ['status' => 'pending']) }}" class="teacher-summary-link mt-2 inline-flex min-h-11 items-center text-xs text-slate-300 underline underline-offset-4"><span class="sr-only sm:not-sr-only">Review pending requests</span><span aria-hidden="true"> →</span></a></div>
    </dl>
    @include('teacher.month-calendar')
@endsection
