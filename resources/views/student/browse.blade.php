@extends('layouts.consultations')
@section('title', 'Book a consultation')
@section('content')
<p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">Student portal</p><h1 class="mt-3 text-3xl font-bold">Find a consultation time</h1><p class="mt-2 text-slate-400">Choose a teacher to see their published availability, then request an open slot.</p>
<form method="GET" class="mt-7 max-w-xl"><label for="teacher" class="mb-2 block text-sm font-medium">Teacher</label><div class="flex gap-3"><select id="teacher" name="teacher" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3">@forelse($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($selectedTeacher?->id === $teacher->id)>{{ $teacher->name }}@if($teacher->role) · {{ $teacher->role->name }}@endif</option>@empty<option>No teacher accounts found</option>@endforelse</select><button class="rounded-lg border border-slate-700 px-4 py-3 text-sm font-semibold hover:bg-slate-800">View</button></div></form>
@error('starts_at')<p class="mt-4 rounded-lg bg-rose-950/40 p-3 text-sm text-rose-200">{{ $message }}</p>@enderror
@error('topic')<p class="mt-4 rounded-lg bg-rose-950/40 p-3 text-sm text-rose-200">{{ $message }}</p>@enderror
@if($selectedTeacher)
<section class="mt-8"><div class="flex flex-wrap items-end justify-between gap-2"><div><h2 class="text-xl font-semibold">{{ $selectedTeacher->name }}</h2><p class="mt-1 text-sm text-slate-400">{{ $selectedTeacher->teacher->subjects->pluck('name')->join(', ') }}</p><p class="mt-1 text-sm text-slate-400">{{ $selectedTeacher->teacher->auto_approve_bookings ? 'Open slots are automatically confirmed when booked.' : 'Requests reserve the slot while awaiting teacher approval.' }}</p></div><span class="text-xs text-slate-500">Times: {{ config('app.timezone') }}</span></div>
    @if($selectedTeacher->teacher->consultation_offerings)<div class="mt-5 rounded-xl border border-slate-800 bg-slate-900 p-4"><h3 class="text-sm font-semibold text-sky-300">What I can help you with</h3><p class="mt-2 whitespace-pre-line break-words text-sm text-slate-300 [overflow-wrap:anywhere]">{{ $selectedTeacher->teacher->consultation_offerings }}</p></div>@endif
    @include('student.teacher-calendar')
    <div class="mt-5 space-y-4">
        @forelse($availability as $period)
            @php
                $periodStart = \Illuminate\Support\Carbon::parse($period->date->toDateString().' '.$period->starts_at);
                $periodEnd = \Illuminate\Support\Carbon::parse($period->date->toDateString().' '.$period->ends_at);
                $cursor = $periodStart->copy();
                $taken = $period->appointments->whereNotIn('status', ['cancelled', 'rejected'])->map(fn($a) => $a->starts_at->format('Y-m-d H:i'))->all();
            @endphp
            <article id="availability-{{ $period->date->toDateString() }}" data-schedule-date="{{ $period->date->toDateString() }}" class="rounded-2xl border border-slate-800 bg-slate-900 p-5"><div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-semibold">{{ $period->date->format('l, F j, Y') }}</h3><span class="text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($period->ends_at)->format('g:i A') }} · {{ $period->slot_minutes }} min slots</span></div>@if($period->location)<p class="mt-1 text-xs text-slate-500">{{ $period->location }}</p>@endif
                <div class="mt-4 flex flex-wrap gap-2">
                    @while($cursor->copy()->addMinutes($period->slot_minutes)->lte($periodEnd))
                        @php $slotStart = $cursor->copy(); $slotEnd = $cursor->copy()->addMinutes($period->slot_minutes); $occupied = in_array($slotStart->format('Y-m-d H:i'), $taken, true); $cursor->addMinutes($period->slot_minutes); @endphp
                        @if($slotStart->isFuture() && ! $occupied)
                            <form method="POST" action="{{ route('consultations.book', $period) }}" class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-700 bg-slate-950 p-2">@csrf<input type="hidden" name="starts_at" value="{{ $slotStart->format('Y-m-d H:i:s') }}"><span class="px-1 text-xs font-medium">{{ $slotStart->format('g:i A') }}</span><input name="topic" required maxlength="500" placeholder="Consultation reason" aria-label="Consultation reason" class="w-40 rounded-md border border-slate-800 bg-slate-900 px-2 py-1.5 text-xs"><button class="rounded-md bg-rose-500 px-3 py-1.5 text-xs font-semibold hover:bg-rose-400">{{ $selectedTeacher->teacher->auto_approve_bookings ? 'Book' : 'Request' }}</button></form>
                        @else<span class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 text-xs text-slate-600 line-through">{{ $slotStart->format('g:i A') }} · {{ $occupied ? 'Booked' : 'Passed' }}</span>@endif
                    @endwhile
                </div>
                <div class="mt-5 border-t border-slate-800 pt-4">
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
        @empty<div class="rounded-2xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-400">This teacher has not published any upcoming consultation times yet.</div>@endforelse
    </div>
</section>
@else<div class="mt-8 rounded-2xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-400">No teacher accounts found. Ask an administrator to create a user with the Teacher role.</div>@endif
@endsection
