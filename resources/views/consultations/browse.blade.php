@extends('consultations.layout')
@section('title', 'Book a consultation')
@section('content')
<p class="text-xs font-semibold uppercase tracking-[.25em] text-rose-300">Student portal</p><h1 class="mt-3 text-3xl font-bold">Find a consultation time</h1><p class="mt-2 text-slate-400">Choose a teacher to see their published availability, then request an open slot.</p>
<form method="GET" class="mt-7 max-w-xl"><label for="teacher" class="mb-2 block text-sm font-medium">Teacher</label><div class="flex gap-3"><select id="teacher" name="teacher" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3">@forelse($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($selectedTeacher?->id === $teacher->id)>{{ $teacher->name }}@if($teacher->role) · {{ $teacher->role->name }}@endif</option>@empty<option>No teacher accounts found</option>@endforelse</select><button class="rounded-lg border border-slate-700 px-4 py-3 text-sm font-semibold hover:bg-slate-800">View</button></div></form>
@error('starts_at')<p class="mt-4 rounded-lg bg-rose-950/40 p-3 text-sm text-rose-200">{{ $message }}</p>@enderror
@if($selectedTeacher)
<section class="mt-8"><div class="flex flex-wrap items-end justify-between gap-2"><div><h2 class="text-xl font-semibold">{{ $selectedTeacher->name }}</h2><p class="mt-1 text-sm text-slate-400">Select an available appointment slot below.</p></div><span class="text-xs text-slate-500">Times shown in your local time</span></div>
    <div class="mt-5 space-y-4">
        @forelse($availability as $period)
            @php
                $periodStart = \Illuminate\Support\Carbon::parse($period->date->toDateString().' '.$period->starts_at);
                $periodEnd = \Illuminate\Support\Carbon::parse($period->date->toDateString().' '.$period->ends_at);
                $cursor = $periodStart->copy();
                $taken = $period->appointments->whereNotIn('status', ['cancelled', 'rejected'])->map(fn($a) => $a->starts_at->format('Y-m-d H:i'))->all();
            @endphp
            <article class="rounded-2xl border border-slate-800 bg-slate-900 p-5"><div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-semibold">{{ $period->date->format('l, F j, Y') }}</h3><span class="text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($period->ends_at)->format('g:i A') }} · {{ $period->slot_minutes }} min slots</span></div>@if($period->location)<p class="mt-1 text-xs text-slate-500">{{ $period->location }}</p>@endif
                <div class="mt-4 flex flex-wrap gap-2">
                    @while($cursor->copy()->addMinutes($period->slot_minutes)->lte($periodEnd))
                        @php $slotStart = $cursor->copy(); $slotEnd = $cursor->copy()->addMinutes($period->slot_minutes); $occupied = in_array($slotStart->format('Y-m-d H:i'), $taken, true); $cursor->addMinutes($period->slot_minutes); @endphp
                        @if($slotStart->isFuture() && ! $occupied)
                            <form method="POST" action="{{ route('consultations.book', $period) }}" class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-700 bg-slate-950 p-2">@csrf<input type="hidden" name="starts_at" value="{{ $slotStart->format('Y-m-d H:i:s') }}"><span class="px-1 text-xs font-medium">{{ $slotStart->format('g:i A') }}</span><input name="topic" maxlength="500" placeholder="Topic (optional)" aria-label="Consultation topic" class="w-36 rounded-md border border-slate-800 bg-slate-900 px-2 py-1.5 text-xs"><button class="rounded-md bg-rose-500 px-3 py-1.5 text-xs font-semibold hover:bg-rose-400">Request</button></form>
                        @else<span class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 text-xs text-slate-600 line-through">{{ $slotStart->format('g:i A') }} · {{ $occupied ? 'Booked' : 'Passed' }}</span>@endif
                    @endwhile
                </div>
            </article>
        @empty<div class="rounded-2xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-400">This teacher has not published any upcoming consultation times yet.</div>@endforelse
    </div>
</section>
@else<div class="mt-8 rounded-2xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-400">No teacher accounts found. Ask an administrator to create a user with the Teacher role.</div>@endif
@endsection
