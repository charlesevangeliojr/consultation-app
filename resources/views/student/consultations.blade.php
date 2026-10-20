@extends('layouts.consultations')
@section('title', 'My consultations')
@section('content')
    @include('student.partials.page-header', ['heading' => 'My consultations', 'description' => 'Your bookings, decisions, and consultation history.', 'actionUrl' => route('consultations.browse'), 'actionLabel' => 'Browse calendar'])
    <p class="mt-4 text-sm text-slate-400">The navigation number includes unfinished consultations and unseen rejections. Rejections stop counting when you view them here; unfinished bookings remain until they end, are completed, or are cancelled.</p>
    <section class="student-card mt-6" data-student-consultations data-seen-url="{{ route('consultations.student-consultations.seen') }}" data-consultations-url="{{ route('consultations.student-consultations.index') }}" aria-label="Your consultations">
        @error('booking')<p role="alert" class="p-4 text-sm text-rose-300">{{ $message }}</p>@enderror
        <div class="student-booking-list">
            @forelse($appointments as $appointment)
                <article class="student-booking" @if($appointment->status === 'rejected' && !$appointment->student_rejection_seen_at) data-unseen-rejection="{{ $appointment->id }}" @endif>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="text-sm font-semibold">{{ $appointment->teacher->name }}</h2><p class="mt-1 text-xs text-slate-400">{{ $appointment->starts_at->format('D, M j, Y · g:i A') }}–{{ $appointment->ends_at->format('g:i A') }} · {{ config('app.timezone') }}</p></div>
                        <span class="student-status student-status-{{ $appointment->status }}">{{ ucfirst($appointment->status) }}</span>
                    </div>
                    @if($appointment->is_proposed)<p class="mt-2 text-xs text-sky-300">Proposed consultation time</p>@endif
                    @if($appointment->topic)<p class="mt-3 border-t border-slate-800 pt-3 text-xs text-slate-400">{{ $appointment->topic }}</p>@endif
                    @if($appointment->availability?->cancelled_at && $appointment->status === 'rejected')<p class="mt-2 text-sm text-rose-300">The teacher cancelled this availability. Its bookings were automatically rejected.</p>@endif
                    @if($appointment->rejectionMessage())<p class="mt-2 text-sm text-rose-300">{{ $appointment->rejectionMessage() }}</p>@endif
                    @if(in_array($appointment->status, ['pending', 'confirmed'], true) && $appointment->starts_at->isFuture())
                        <form method="POST" action="{{ route('consultations.appointments.cancel', $appointment) }}" class="mt-3" onsubmit="return confirm('Cancel this consultation? You can book another available time afterward.');">@csrf @method('PATCH')<button class="min-h-11 rounded-lg border border-rose-900 px-3 py-2 text-xs text-rose-300">Cancel booking</button></form>
                    @endif
                </article>
            @empty<div class="student-empty-state">No consultations yet. Browse the calendar to request a consultation.</div>@endforelse
        </div>
        @if($appointments->hasPages())<div class="p-5">{{ $appointments->links() }}</div>@endif
    </section>
@endsection
