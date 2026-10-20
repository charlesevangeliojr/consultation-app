@extends('admin.layout')
@section('title', 'Accounts')
@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-semibold uppercase tracking-[0.25em] text-rose-400">People &amp; access</p><h2 class="mt-3 text-3xl font-bold sm:text-4xl">Accounts</h2><p class="mt-3 text-slate-400">Manage user details, student account status, and recent activity.</p></div>
    </div>
    @if(session('status'))<p role="status" class="mt-6 rounded-lg border border-emerald-900 bg-emerald-950/50 p-4 text-sm text-emerald-200">{{ session('status') }}</p>@endif
    <nav class="account-group-tabs" aria-label="Account groups">
        @foreach(['all' => 'All', 'admins' => 'Admins', 'teachers' => 'Teachers', 'students' => 'Students'] as $key => $label)
            <a href="{{ route('admin.accounts', array_filter(['group' => $key, 'search' => $search])) }}" @if($key === 'teachers' && $pendingCount > 0) class="account-group-needs-approval" @endif @if($group === $key || ($key === 'teachers' && $group === 'pending')) aria-current="page" @endif>{{ $label }}@if($key === 'teachers' && $pendingCount > 0) <span class="account-pending-count" aria-label="{{ $pendingCount }} teachers need scheduling approval">{{ $pendingCount }}</span>@endif</a>
        @endforeach
    </nav>
    <p class="mt-6 text-sm text-slate-400">Online means activity within the last 5 minutes. Student accounts become inactive after 2 years without activity. Active / inactive is separate from online / offline. Refresh this page to update activity.</p>
    <form method="GET" action="{{ route('admin.accounts') }}" role="search" aria-label="Search accounts" class="mt-6 rounded-xl border border-slate-800 bg-slate-900 p-4">
        <input type="hidden" name="group" value="{{ $group }}">
        <label for="account-search" class="block text-sm font-medium text-slate-200">Search users</label>
        <div class="mt-2 flex flex-wrap gap-3">
            <input id="account-search" name="search" type="search" maxlength="150" value="{{ $search }}" placeholder="Name, email, LRN, or section" aria-describedby="account-search-help" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white">
            <button type="submit" class="rounded-lg bg-rose-500 px-4 py-3 text-sm font-semibold hover:bg-rose-400">Search</button>
            @if($search !== '')<a href="{{ route('admin.accounts', ['group' => $group]) }}" class="rounded-lg border border-slate-700 px-4 py-3 text-sm font-semibold text-slate-200 hover:bg-slate-800">Clear</a>@endif
        </div>
        <p id="account-search-help" class="mt-2 text-xs text-slate-400">Search the selected group by name, email, student LRN, or section.</p>
        @error('search')<p role="alert" class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
        <p role="status" class="mt-3 text-sm text-slate-400">{{ $users->total() }} {{ \Illuminate\Support\Str::plural('user', $users->total()) }}{{ $search !== '' ? ' matching your search' : ' in total' }}.</p>
    </form>
    <p class="mt-6 text-sm text-slate-400">Select a user to view account information and available actions.</p>
    <div class="booking-records-table mt-4" data-account-records>
        <table><caption class="sr-only">Accounts. Select a name or tap a row to view details.</caption><thead><tr><th scope="col">Name</th><th scope="col">Role</th></tr></thead><tbody>
            @forelse($users as $user)
                @php($needsApproval = $user->isTeacher() && !$user->teacher?->consultation_approved)
                <tr data-record-open="account-detail-{{ $user->id }}" @if($needsApproval) class="account-needs-approval" @endif><td><button type="button" aria-haspopup="dialog" aria-controls="account-detail-{{ $user->id }}" aria-label="View account for {{ $user->name }}"><strong>{{ $user->name }}</strong>@if($needsApproval)<span class="account-approval-label">Needs scheduling approval</span>@endif</button></td><td><span class="account-record-role">{{ $user->isAdmin() ? 'Super Admin' : ($user->role?->name ?? 'Custom permissions') }}</span></td></tr>
            @empty<tr><td colspan="2" class="booking-records-empty">No users found.</td></tr>@endforelse
        </tbody></table>
    </div>
    @foreach($users as $user)
        <dialog id="account-detail-{{ $user->id }}" class="booking-detail-dialog" data-record-dialog aria-labelledby="account-title-{{ $user->id }}">
            <header class="booking-detail-header"><div><p>USER ACCOUNT</p><h2 id="account-title-{{ $user->id }}">Account details</h2></div><button type="button" data-record-close aria-label="Close account details">×</button></header>
            <div class="booking-detail-body"><dl class="booking-detail-fields">
                <div class="booking-detail-wide"><dt>Name</dt><dd>{{ $user->name }}</dd></div>
                <div><dt>Role</dt><dd>{{ $user->isAdmin() ? 'Super Admin' : ($user->role?->name ?? 'Custom permissions') }}</dd></div>
                <div><dt>Email</dt><dd>{{ $user->email ?: 'Not provided' }}</dd></div>
                @if($user->isStudent())
                    <div><dt>LRN</dt><dd>{{ $user->student?->lrn ?: 'Not provided' }}</dd></div>
                    <div><dt>Account status</dt><dd>{{ ucfirst($user->student?->status ?? 'active') }}</dd></div>
                    <div><dt>Grade level</dt><dd>{{ $user->student?->gradeLevel?->name ?: 'Not assigned' }}</dd></div>
                    <div><dt>Section</dt><dd>Section: {{ $user->student?->section?->name ?: 'Not assigned' }}</dd></div>
                @else<div><dt>Account status</dt><dd>Not applicable</dd></div>@endif
                <div><dt>Online status</dt><dd>{{ $user->isOnline() ? 'Online' : 'Offline' }}</dd></div>
                <div class="booking-detail-wide"><dt>Last seen</dt><dd>@if($user->last_seen_at)<time datetime="{{ $user->last_seen_at->toIso8601String() }}">{{ $user->last_seen_at->diffForHumans() }}</time><span>{{ $user->last_seen_at->format('M j, Y g:i A') }} · {{ config('app.timezone') }}</span>@else Not yet recorded @endif</dd></div>
            </dl>
            @if($user->isTeacher()) @include('admin.partials.teacher-approval', ['teacherAccount' => $user]) @endif
            </div>
            <footer class="booking-detail-footer account-record-footer">
                @if(!auth()->user()->canManage($user, 'edit') && !auth()->user()->canManage($user, 'delete'))<p>Protected account</p>@endif
                <div class="account-record-actions">
                    @if(auth()->user()->canManage($user, 'edit'))<a href="{{ route('admin.accounts.edit', $user) }}">Edit account</a>@endif
                    @if(auth()->user()->canManage($user, 'delete') && !$user->isAdmin() && !auth()->user()->is($user))<button type="button" data-open-delete="delete-user-{{ $user->id }}">Delete</button>@endif
                    <button type="button" data-record-close>Close</button>
                </div>
            </footer>
        </dialog>
        @if(auth()->user()->canManage($user, 'delete') && !$user->isAdmin() && !auth()->user()->is($user))
            <dialog id="delete-user-{{ $user->id }}" data-account-delete-dialog aria-labelledby="delete-title-{{ $user->id }}" class="booking-detail-dialog">
                <div class="p-6"><h3 id="delete-title-{{ $user->id }}" class="text-lg font-semibold">Delete user?</h3><p class="mt-3 text-sm leading-6 text-slate-300">You’re about to permanently delete <strong>{{ $user->name }}</strong> ({{ $user->email }}). This cannot be undone.</p>
                    <form method="POST" action="{{ route('admin.accounts.destroy', $user) }}" class="mt-6 flex flex-wrap justify-end gap-3">@csrf @method('DELETE')<button type="button" data-close-delete autofocus class="min-h-11 rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-200">Cancel</button><button class="min-h-11 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white">Delete user</button></form>
                </div>
            </dialog>
        @endif
    @endforeach
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
