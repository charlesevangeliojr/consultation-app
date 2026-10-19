@extends('admin.layout')
@section('title', 'Consultation monitoring')
@section('content')
<h2 class="text-3xl font-bold">Consultation monitoring</h2>
<p class="mt-3 text-slate-400">Read-only booking records. Teachers control booking approval; administrators manage teacher permission through Accounts.</p>
@if(session('status'))<p role="status" class="mt-5 rounded-lg border border-emerald-900 bg-emerald-950/50 p-4 text-sm text-emerald-200">{{ session('status') }}</p>@endif
@include('admin.partials.consultation-calendar')
<details class="mt-6"><summary class="min-h-11 cursor-pointer text-sm font-semibold text-slate-400">Teachers &amp; permission</summary><div class="mt-4 grid gap-3 sm:grid-cols-2">
@forelse($teachers as $teacher)<article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
    <p class="font-semibold">{{ $teacher->name }}</p>
    @include('admin.partials.teacher-approval', ['teacherAccount' => $teacher])
    @if(auth()->user()->canManage($teacher))<a href="{{ route('admin.accounts.edit', $teacher) }}" class="mt-3 inline-block text-sm text-rose-300">Edit profile / permission →</a>@endif
</article>@empty<p class="text-slate-500">No teachers registered.</p>@endforelse
</div></details>
<section class="mt-10"><h3 class="text-xl font-semibold">Teacher availability</h3><div class="mt-4 grid gap-3 sm:grid-cols-2">
@forelse($availability as $period)<article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
    <p class="font-semibold">{{ $period->teacher->name }}</p><p class="mt-2 text-sm text-slate-300">{{ $period->date->format('M j, Y') }} · {{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($period->ends_at)->format('g:i A') }}</p>
    <p class="mt-2 text-xs text-slate-400">Student-selected time ranges · {{ $period->queued_count }} queued · {{ $period->cancelled_at ? 'Cancelled' : 'Published' }}</p>
    @if($period->location)<p class="mt-2 text-xs text-slate-500">{{ $period->location }}</p>@endif
</article>@empty<p class="text-sm text-slate-500">No availability published for this date.</p>@endforelse
</div><div class="mt-4">{{ $availability->withQueryString()->links() }}</div></section>
<section class="mt-10" aria-labelledby="booking-records-heading">
    <h3 id="booking-records-heading" class="text-xl font-semibold">Booking records</h3>
    <p class="mt-2 text-sm text-slate-400">Select a booking to view its full details. Times: {{ config('app.timezone') }}.</p>
    <div class="booking-records-table mt-4">
        <table><caption class="sr-only">Booking records. Select the date and time or tap a row to view details.</caption><thead><tr><th scope="col">Date &amp; time</th><th scope="col">Teacher</th></tr></thead><tbody>
            @forelse($appointments as $appointment)
                <tr data-booking-open="booking-detail-{{ $appointment->id }}">
                    <td><button type="button" aria-haspopup="dialog" aria-controls="booking-detail-{{ $appointment->id }}" aria-label="View booking with {{ $appointment->teacher->name }} on {{ $appointment->starts_at->format('M j, Y') }} at {{ $appointment->starts_at->format('g:i A') }}"><strong>{{ $appointment->starts_at->format('M j, Y') }}</strong><span>{{ $appointment->starts_at->format('g:i A') }}–{{ $appointment->ends_at->format('g:i A') }}</span></button></td>
                    <td>{{ $appointment->teacher->name }}</td>
                </tr>
            @empty<tr><td colspan="2" class="booking-records-empty">No bookings yet. Select another date to view its records.</td></tr>@endforelse
        </tbody></table>
    </div>
    @foreach($appointments as $appointment)
        <dialog id="booking-detail-{{ $appointment->id }}" class="booking-detail-dialog" aria-labelledby="booking-title-{{ $appointment->id }}" data-booking-dialog>
            <header class="booking-detail-header"><div><p>CONSULTATION RECORD</p><h2 id="booking-title-{{ $appointment->id }}">Booking details</h2></div><button type="button" data-booking-close aria-label="Close booking details">×</button></header>
            <div class="booking-detail-body">
                <span class="booking-detail-status" data-status="{{ $appointment->status }}">{{ ucfirst($appointment->status) }}</span>
                <dl class="booking-detail-fields">
                    <div class="booking-detail-wide"><dt>Date &amp; time</dt><dd>{{ $appointment->starts_at->format('l, F j, Y') }}<span>{{ $appointment->starts_at->format('g:i A') }}–{{ $appointment->ends_at->format('g:i A') }} · {{ (int) $appointment->starts_at->diffInMinutes($appointment->ends_at) }} minutes · {{ config('app.timezone') }}</span></dd></div>
                    <div><dt>Teacher</dt><dd>{{ $appointment->teacher->name }}</dd></div>
                    <div><dt>Student</dt><dd>{{ $appointment->student->name }}</dd></div>
                    <div><dt>Request type</dt><dd>{{ $appointment->is_proposed ? 'Student-proposed time' : 'Published availability' }}</dd></div>
                    <div><dt>Location</dt><dd>{{ $appointment->availability?->location ?: 'Not specified' }}</dd></div>
                    <div class="booking-detail-wide"><dt>Consultation reason</dt><dd class="booking-detail-reason">{{ $appointment->topic ?: 'No reason provided.' }}</dd></div>
                </dl>
                @if($appointment->availability?->cancelled_at)<p class="booking-detail-notice">The teacher cancelled this availability.</p>@endif
                @if($appointment->rejectionMessage())<p class="booking-detail-notice">{{ $appointment->rejectionMessage() }}</p>@endif
            </div>
            <footer class="booking-detail-footer"><p>Read-only record. Teachers manage booking approval.</p><button type="button" data-booking-close>Close</button></footer>
        </dialog>
    @endforeach
    <div class="mt-4">{{ $appointments->withQueryString()->links() }}</div>
</section>
@endsection
