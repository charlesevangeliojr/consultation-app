<section class="teacher-approval-panel" aria-label="Scheduling permission for {{ $teacherAccount->name }}">
    <div class="teacher-approval-heading"><h3>Scheduling permission</h3><span class="teacher-approval-status" data-approved="{{ $teacherAccount->teacher?->consultation_approved ? 'true' : 'false' }}">{{ $teacherAccount->teacher?->consultation_approved ? 'Approved' : 'Not approved' }}</span></div>
    <p>{{ $teacherAccount->teacher?->consultation_approved ? 'This teacher may publish availability and accept bookings after completing their profile.' : 'This teacher cannot publish availability or accept bookings until an administrator approves scheduling.' }}</p>
    @if(auth()->user()->canAccess('accounts', 'edit') && auth()->user()->canManage($teacherAccount, 'edit'))
        <form method="POST" action="{{ route('admin.accounts.school-profile', $teacherAccount) }}">
            @csrf @method('PUT')
            <input type="hidden" name="consultation_approved" value="{{ $teacherAccount->teacher?->consultation_approved ? '0' : '1' }}">
            <button type="submit">{{ $teacherAccount->teacher?->consultation_approved ? 'Revoke scheduling' : 'Approve scheduling' }}</button>
        </form>
    @endif
    <p class="teacher-approval-help">Revoking blocks new schedules, requests, and confirmations. Existing records are kept. Only teachers approve individual bookings.</p>
</section>
