@extends('admin.layout')
@section('title', 'Consultation monitoring')
@section('content')
<h2 class="text-3xl font-bold">Consultation monitoring</h2>
<p class="mt-3 text-slate-400">Read-only booking records. Teachers control booking approval; administrators manage teacher permission through Accounts.</p>
<section class="mt-8"><h3 class="text-xl font-semibold">Teachers &amp; permission</h3><div class="mt-4 grid gap-3 sm:grid-cols-2">
@forelse($teachers as $teacher)<article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
    <p class="font-semibold">{{ $teacher->name }}</p><p class="mt-2 text-sm {{ $teacher->teacher?->consultation_approved ? 'text-emerald-300' : 'text-amber-300' }}">{{ $teacher->teacher?->consultation_approved ? 'Approved for consultations' : 'Awaiting approval / revoked' }}</p>
    <p class="mt-2 text-xs text-slate-400">{{ $teacher->teacher?->subjects->pluck('name')->join(', ') ?: 'No subjects assigned' }}</p>
    @if(auth()->user()->canManage($teacher))<a href="{{ route('admin.accounts.edit', $teacher) }}" class="mt-3 inline-block text-sm text-rose-300">Edit profile / permission →</a>@endif
</article>@empty<p class="text-slate-500">No teachers registered.</p>@endforelse
</div></section>
<section class="mt-10"><h3 class="text-xl font-semibold">Teacher availability</h3><div class="mt-4 grid gap-3 sm:grid-cols-2">
@forelse($availability as $period)<article class="rounded-xl border border-slate-800 bg-slate-900 p-4">
    <p class="font-semibold">{{ $period->teacher->name }}</p><p class="mt-2 text-sm text-slate-300">{{ $period->date->format('M j, Y') }} · {{ \Illuminate\Support\Carbon::parse($period->starts_at)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($period->ends_at)->format('g:i A') }}</p>
    <p class="mt-2 text-xs text-slate-400">Student-selected time ranges · {{ $period->queued_count }} queued · {{ $period->cancelled_at ? 'Cancelled' : 'Published' }}</p>
    @if($period->location)<p class="mt-2 text-xs text-slate-500">{{ $period->location }}</p>@endif
</article>@empty<p class="text-sm text-slate-500">No availability published.</p>@endforelse
</div><div class="mt-4">{{ $availability->withQueryString()->links() }}</div></section>
<section class="mt-10"><h3 class="text-xl font-semibold">Booking records</h3><div class="mt-4 overflow-x-auto rounded-xl border border-slate-800">
<table class="w-full text-left text-sm"><thead class="bg-slate-900 text-slate-400"><tr><th class="p-4">Date &amp; time</th><th class="p-4">Teacher</th><th class="p-4">Student</th><th class="p-4">Reason</th><th class="p-4">Status</th></tr></thead><tbody>
@forelse($appointments as $appointment)<tr class="border-t border-slate-800"><td class="p-4 whitespace-nowrap">{{ $appointment->starts_at->format('M j, Y · g:i A') }}–{{ $appointment->ends_at->format('g:i A') }}@if($appointment->is_proposed)<span class="block text-xs text-sky-300">Student-proposed time</span>@endif</td><td class="p-4">{{ $appointment->teacher->name }}</td><td class="p-4">{{ $appointment->student->name }}</td><td class="p-4">{{ $appointment->topic }}</td><td class="p-4 capitalize">{{ $appointment->status }}@if($appointment->availability?->cancelled_at)<span class="mt-1 block text-xs text-rose-300">Availability cancelled</span>@endif @if($appointment->rejectionMessage())<span class="mt-1 block text-xs text-rose-300">{{ $appointment->rejectionMessage() }}</span>@endif</td></tr>
@empty<tr><td colspan="5" class="p-6 text-slate-500">No bookings yet.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $appointments->withQueryString()->links() }}</div></section>
@endsection
