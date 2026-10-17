@extends('layouts.consultations')
@section('title', 'Teacher availability')
@section('content')
    @include('teacher.partials.page-header', ['heading' => 'Availability', 'description' => 'Publish consultation windows. Students choose their start and end times within each window.'])
    @if($profile->consultation_approved)
        <section id="teacher-publish" class="mt-6 rounded-2xl border border-slate-800 bg-slate-900 p-4 sm:p-6" aria-labelledby="publish-heading">
            <h2 id="publish-heading" class="text-lg font-semibold">Publish availability</h2>
            <p class="mt-1 text-xs leading-5 text-slate-400">Choose a future window, for example 1:00–5:00 PM. Each student can request up to {{ $profile->max_consultation_minutes }} minutes per consultation. <a href="{{ route('consultations.teacher.preferences') }}" class="text-rose-300 underline">Change time limit</a>. Times: {{ config('app.timezone') }}.</p>
            <form method="POST" action="{{ route('consultations.availability.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @csrf
                <div><label for="date" class="mb-2 block text-sm font-medium">Date</label><input id="date" name="date" type="date" min="{{ today()->toDateString() }}" value="{{ old('date') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3"></div>
                <div><label for="starts_at" class="mb-2 block text-sm font-medium">Start time</label><input id="starts_at" name="starts_at" type="time" value="{{ old('starts_at') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3"></div>
                <div><label for="ends_at" class="mb-2 block text-sm font-medium">End time</label><input id="ends_at" name="ends_at" type="time" value="{{ old('ends_at') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3"></div>
                <div><label for="location" class="mb-2 block text-sm font-medium">Location</label><input id="location" name="location" value="{{ old('location') }}" maxlength="120" placeholder="Room / office" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-3"></div>
                <div class="flex items-end"><button type="submit" class="min-h-11 w-full rounded-lg bg-rose-500 px-4 py-3 text-sm font-semibold hover:bg-rose-400">Publish times</button></div>
            </form>
        </section>
    @endif
    <section class="mt-8" aria-labelledby="published-heading">
        <div class="flex flex-wrap items-center justify-between gap-3"><h2 id="published-heading" class="text-lg font-semibold">Published availability</h2><span class="text-xs text-slate-400">{{ $availability->total() }} upcoming {{ \Illuminate\Support\Str::plural('window', $availability->total()) }}</span></div>
        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($availability as $period)
                <article class="flex min-w-0 flex-col rounded-xl border border-slate-800 bg-slate-900 p-5">
                    <p class="text-xs font-medium text-sky-300">Published window</p>
                    <h3 class="mt-2 font-semibold">{{ $period->date->format('l, F j, Y') }}</h3>
                    <p class="mt-2 text-sm text-slate-300">{{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($period->ends_at)->format('g:i A') }}</p>
                    <p class="mt-2 text-xs text-slate-400">Up to {{ $profile->max_consultation_minutes }} minutes per consultation @if($period->location) · {{ $period->location }} @endif</p>
                    <a href="{{ route('consultations.teacher', ['month' => $period->date->format('Y-m'), 'day' => $period->date->toDateString()]) }}#teacher-month-calendar" class="mt-3 inline-flex min-h-11 items-center text-xs text-sky-300 underline underline-offset-4">View in calendar →</a>
                    <form method="POST" action="{{ route('consultations.availability.cancel', $period) }}" class="mt-auto border-t border-slate-800 pt-4" onsubmit="return confirm('Cancel this availability? All pending and confirmed bookings will be rejected.');">
                        @csrf @method('PATCH')<button class="min-h-11 rounded-lg border border-rose-900 px-3 py-2 text-xs text-rose-300">Cancel availability &amp; reject queue</button>
                    </form>
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-400 md:col-span-2 xl:col-span-3">No upcoming availability. @if($profile->consultation_approved)Publish your first consultation window above.@else An administrator must approve your consultation permission before you can publish.@endif</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $availability->links() }}</div>
    </section>
@endsection
