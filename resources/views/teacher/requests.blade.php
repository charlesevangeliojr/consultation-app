@extends('layouts.consultations')
@section('title', 'Consultation requests')
@section('content')
    @include('teacher.partials.page-header', ['heading' => 'Consultation requests', 'description' => 'Review student requests, manage confirmed bookings, and browse consultation history. Requests are ordered by consultation time.', 'compactMobile' => true])
    <dl class="teacher-summary mt-6 grid gap-3 sm:grid-cols-3" aria-label="Request totals">
        @foreach(['pending' => ['Awaiting your review', 'text-amber-300'], 'confirmed' => ['Confirmed bookings', 'text-emerald-300'], 'completed' => ['Completed consultations', 'text-slate-200']] as $key => [$label, $color])
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4"><dt class="text-xs text-slate-400"><span class="sm:hidden">{{ ucfirst($key) }}</span><span class="hidden sm:inline">{{ $label }}</span></dt><dd class="mt-2 text-2xl font-semibold {{ $color }}">{{ $statusCounts[$key] ?? 0 }}</dd></div>
        @endforeach
    </dl>
    <section id="teacher-requests" class="mt-6" aria-label="Consultation request list">
        <nav aria-label="Filter requests by status" class="flex flex-wrap gap-2">
            @foreach(['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('consultations.teacher.requests', $key ? ['status' => $key] : []) }}" @if(($status ?? '') === $key) aria-current="page" @endif class="inline-flex min-h-11 items-center gap-2 rounded-lg border px-3 py-2 text-xs {{ ($status ?? '') === $key ? 'border-rose-400 bg-rose-500/10 text-rose-200' : 'border-slate-700 text-slate-300 hover:bg-slate-800' }}">{{ $label }} <span class="text-[10px] opacity-70">{{ $key ? ($statusCounts[$key] ?? 0) : $statusCounts->sum() }}</span></a>
            @endforeach
        </nav>
        <p class="mt-4 text-xs text-slate-400">Pending requests reserve the selected time range until confirmed, rejected, or cancelled. Times: {{ config('app.timezone') }}.</p>
        <div class="mt-4 space-y-3">
            @forelse($appointments as $appointment)
                <article class="flex min-w-0 flex-col justify-between gap-4 rounded-xl border border-slate-800 bg-slate-900 p-5 lg:flex-row lg:items-center">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2"><h2 class="font-semibold">{{ $appointment->starts_at->format('D, M j, Y · g:i A') }}</h2><span @class(['rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide', 'bg-amber-400/10 text-amber-200' => $appointment->status === 'pending', 'bg-emerald-400/10 text-emerald-200' => $appointment->status === 'confirmed', 'bg-slate-700 text-slate-300' => in_array($appointment->status, ['completed', 'cancelled', 'rejected'])])>{{ $appointment->status }}</span></div>
                        <p class="mt-2 text-sm text-slate-300">{{ $appointment->student->name }} · Until {{ $appointment->ends_at->format('g:i A') }} @if($appointment->availability?->location) · {{ $appointment->availability->location }} @endif</p>
                        @if($appointment->is_proposed)<p class="mt-2 text-xs text-sky-300">Student-proposed time · Manual approval required</p>@endif
                        @if($appointment->rejectionMessage())<p class="mt-2 text-xs text-rose-300">{{ $appointment->rejectionMessage() }}</p>@endif
                        @if($appointment->topic)<p class="mt-2 text-sm text-slate-400"><span class="text-slate-500">Reason:</span> {{ $appointment->topic }}</p>@endif
                    </div>
                    <div class="request-actions grid min-w-0 grid-cols-2 gap-2 sm:flex sm:flex-wrap lg:shrink-0">
                        @if($appointment->status === 'pending')
                            @if($profile->consultation_approved && $appointment->starts_at->isFuture())<form method="POST" action="{{ route('consultations.appointments.update', $appointment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="confirmed"><button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold">Confirm</button></form>@endif
                            <form method="POST" action="{{ route('consultations.appointments.update', $appointment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="rounded-lg border border-slate-700 px-3 py-2 text-xs">Reject</button></form>
                        @elseif($appointment->status === 'confirmed')
                            <form method="POST" action="{{ route('consultations.appointments.update', $appointment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button class="rounded-lg border border-slate-700 px-3 py-2 text-xs">Mark completed</button></form>
                            <form method="POST" action="{{ route('consultations.appointments.update', $appointment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><button class="rounded-lg border border-rose-900 px-3 py-2 text-xs text-rose-300">Cancel booking</button></form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-700 p-8 text-center"><p class="text-sm text-slate-400">{{ $status ? 'No '.$status.' consultations found.' : 'No consultation requests yet.' }}</p><a href="{{ route('consultations.teacher.availability') }}" class="mt-3 inline-flex min-h-11 items-center text-sm text-sky-300 underline underline-offset-4">Manage availability →</a></div>
            @endforelse
        </div>
        <div class="mt-6">{{ $appointments->links() }}</div>
    </section>
@endsection
