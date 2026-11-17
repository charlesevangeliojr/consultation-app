@extends('admin.layout')
@section('title', 'Accounts')
@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-rose-400">People &amp; access</p>
            <h2 class="mt-1 text-2xl font-bold sm:mt-2 sm:text-3xl">Accounts</h2>
            <p class="mt-1 hidden text-sm text-slate-400 sm:block">Manage user details, student account status, and recent activity.</p>
        </div>
    </div>
    @if(session('status'))<p role="status" class="mt-4 rounded-lg border border-emerald-900 bg-emerald-950/50 p-3 text-sm text-emerald-200 sm:mt-6 sm:p-4">{{ session('status') }}</p>@endif
    <nav class="account-group-tabs !mt-4" aria-label="Account groups">
        @foreach(['all' => 'All', 'admins' => 'Admins', 'teachers' => 'Teachers', 'students' => 'Students'] as $key => $label)
            <a href="{{ route('admin.accounts', array_filter(['group' => $key, 'search' => $search, 'per_page' => $perPage !== 15 ? $perPage : null])) }}" @if($key === 'teachers' && $pendingCount > 0) class="account-group-needs-approval" @endif @if($group === $key || ($key === 'teachers' && $group === 'pending')) aria-current="page" @endif>{{ $label }}@if($key === 'teachers' && $pendingCount > 0) <span class="account-pending-count" aria-label="{{ $pendingCount }} teachers need scheduling approval">{{ $pendingCount }}</span>@endif</a>
        @endforeach
    </nav>
    <form method="GET" action="{{ route('admin.accounts') }}" role="search" aria-label="Search accounts" class="mt-3 rounded-xl border border-slate-800 bg-slate-900 p-3 sm:mt-4 sm:p-4">
        <input type="hidden" name="group" value="{{ $group }}">
        @if($perPage !== 15)<input type="hidden" name="per_page" value="{{ $perPage }}">@endif
        <label for="account-search" class="sr-only">Search users</label>
        <div class="flex flex-wrap gap-2 sm:gap-3">
            <input id="account-search" name="search" type="search" maxlength="150" value="{{ $search }}" placeholder="Search name, email, LRN, or section" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white sm:px-4">
            <button type="submit" class="rounded-lg bg-rose-500 px-4 py-2.5 text-sm font-semibold hover:bg-rose-400">Search</button>
            @if($search !== '')<a href="{{ route('admin.accounts', array_filter(['group' => $group, 'per_page' => $perPage !== 15 ? $perPage : null])) }}" class="rounded-lg border border-slate-700 px-3 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-800">Clear</a>@endif
        </div>
        @error('search')<p role="alert" class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
        <p role="status" class="mt-2 text-xs text-slate-400">{{ $users->total() }} {{ \Illuminate\Support\Str::plural('user', $users->total()) }}{{ $search !== '' ? ' matching your search' : ' in total' }}.</p>
    </form>
    <div class="booking-records-table mt-3 sm:mt-4" data-account-records>
        <table><caption class="sr-only">Accounts. Select a name or tap a row to view details.</caption><thead><tr><th scope="col">Name</th><th scope="col">Role</th></tr></thead><tbody>
            @forelse($users as $user)
                @php($needsApproval = $user->isTeacher() && !$user->teacher?->consultation_approved)
                @php($photoPath = $user->isTeacher() ? $user->teacher?->profile_photo_path : ($user->isStudent() ? $user->student?->profile_photo_path : null))
                <tr data-record-open="account-detail-{{ $user->id }}" @if($needsApproval) class="account-needs-approval" @endif><td><button type="button" aria-haspopup="dialog" aria-controls="account-detail-{{ $user->id }}" aria-label="View account for {{ $user->name }}"><div class="flex items-center gap-3">@if($photoPath)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photoPath) }}" alt="{{ $user->name }} profile picture" class="h-9 w-9 shrink-0 rounded-full border border-slate-700 object-cover">@else<div aria-hidden="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-700 bg-slate-800 text-xs font-semibold text-slate-200">{{ mb_substr($user->name, 0, 1) }}</div>@endif<div class="min-w-0"><strong class="block truncate">{{ $user->name }}</strong>@if($needsApproval)<span class="account-approval-label">Needs scheduling approval</span>@endif</div></div></button></td><td><span class="account-record-role">{{ $user->isAdmin() ? 'Super Admin' : ($user->role?->name ?? 'Custom permissions') }}</span></td></tr>
            @empty<tr><td colspan="2" class="booking-records-empty">No users found.</td></tr>@endforelse
        </tbody></table>
    </div>
    @foreach($users as $user)
        @php($photoPath = $user->isTeacher() ? $user->teacher?->profile_photo_path : ($user->isStudent() ? $user->student?->profile_photo_path : null))
        <dialog id="account-detail-{{ $user->id }}" class="booking-detail-dialog" data-record-dialog aria-labelledby="account-title-{{ $user->id }}">
            <header class="booking-detail-header"><div class="flex items-center gap-3">@if($photoPath)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($photoPath) }}" alt="{{ $user->name }} profile picture" class="h-12 w-12 shrink-0 rounded-full border border-slate-700 object-cover">@else<div aria-hidden="true" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-slate-700 bg-slate-800 text-sm font-semibold text-slate-200">{{ mb_substr($user->name, 0, 1) }}</div>@endif<div><p>USER ACCOUNT</p><h2 id="account-title-{{ $user->id }}">Account details</h2></div></div><button type="button" data-record-close aria-label="Close account details">×</button></header>
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
    <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-900 px-3 py-2.5 text-sm text-slate-300 sm:mt-4 sm:px-4 sm:py-3">
        <p class="text-xs text-slate-400">Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} · Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</p>
        <div class="flex flex-wrap items-center gap-3 sm:gap-4">
            <form method="GET" action="{{ route('admin.accounts') }}" class="flex items-center gap-2">
                <input type="hidden" name="group" value="{{ $group }}">
                @if($search !== '')<input type="hidden" name="search" value="{{ $search }}">@endif
                <label for="accounts-per-page" class="text-xs text-slate-400">Per page</label>
                <select id="accounts-per-page" name="per_page" onchange="this.form.submit()" class="rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1.5 text-xs text-white">
                    @foreach([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
            @if($users->hasPages())<div>{{ $users->links() }}</div>@endif
        </div>
    </div>
    <p class="mt-3 text-xs leading-5 text-slate-400">Online means activity within the last 5 minutes. Student accounts become inactive after 2 years without activity. Refresh to update activity.</p>
@endsection
