@extends('admin.layout')

@section('title', 'Accounts')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-rose-400">People &amp; access</p>
            <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Accounts</h2>
            <p class="mt-3 text-slate-400">Manage user details, student account status, and recent activity.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @if (auth()->user()->canAccess('accounts', 'add'))
                <a href="{{ route('admin.accounts.create') }}" class="rounded-lg bg-rose-500 px-5 py-3 font-semibold transition hover:bg-rose-400">Add user</a>
            @endif
        </div>
    </div>

    @if (session('status'))
        <p role="status" class="mt-6 rounded-lg border border-emerald-900 bg-emerald-950/50 p-4 text-sm text-emerald-200">{{ session('status') }}</p>
    @endif

    <p class="mt-6 text-sm text-slate-400">Online means activity within the last 5 minutes. Student accounts become inactive after 2 years without activity. Active / inactive is separate from online / offline. Refresh this page to update activity.</p>
    <div class="mt-8 overflow-x-auto rounded-2xl border border-slate-800">
        <table class="w-full min-w-[620px] text-left text-sm">
            <thead class="bg-slate-900 text-slate-300">
                <tr><th class="px-5 py-3 font-medium">Name</th><th class="px-5 py-3 font-medium">LRN / Email</th><th class="px-5 py-3 font-medium">Role</th><th class="px-5 py-3 font-medium">Account status</th><th class="px-5 py-3 font-medium">Online status</th><th class="px-5 py-3 font-medium">Last seen</th><th class="px-5 py-3 text-right font-medium">Action</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-800 bg-slate-900/50">
                @forelse ($users as $user)
                    <tr class="transition-colors hover:bg-slate-800/60">
                        <td class="px-5 py-4 font-medium">{{ $user->name }}</td>
                        <td class="px-5 py-4 text-slate-300">{{ $user->isStudent() ? ($user->student?->lrn ?: $user->email) : $user->email }}</td>
                        <td class="px-5 py-4">
                            @if ($user->isAdmin())
                                <span class="inline-flex rounded-full border border-amber-400/30 bg-amber-400/10 px-2.5 py-1 text-xs font-semibold text-amber-200">Super Admin</span>
                            @else
                                <span @class(['inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold', 'border-sky-400/30 bg-sky-400/10 text-sky-200' => $user->role, 'border-slate-600 bg-slate-800 text-slate-300' => ! $user->role])>{{ $user->role?->name ?? 'Custom permissions' }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($user->isStudent())
                                <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-400/10 text-emerald-200' => ($user->student?->status ?? 'active') === 'active', 'bg-slate-700 text-slate-300' => $user->student?->status === 'inactive'])>{{ ucfirst($user->student?->status ?? 'active') }}</span>
                            @else
                                <span class="text-slate-500">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4"><span @class(['text-emerald-300' => $user->isOnline(), 'text-slate-400' => !$user->isOnline()])>{{ $user->isOnline() ? 'Online' : 'Offline' }}</span></td>
                        <td class="px-5 py-4 text-slate-400">@if($user->last_seen_at)<time datetime="{{ $user->last_seen_at->toIso8601String() }}" title="{{ $user->last_seen_at->format('M j, Y g:i A').' '.config('app.timezone') }}">{{ $user->last_seen_at->diffForHumans() }}</time>@else Not yet recorded @endif</td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-4">
                                @if (auth()->user()->canManage($user, 'edit'))
                                    <a href="{{ route('admin.accounts.edit', $user) }}" class="font-semibold text-rose-300 hover:text-rose-200">Edit</a>
                                @endif
                                @if (auth()->user()->canManage($user, 'delete') && ! $user->isAdmin() && ! auth()->user()->is($user))
                                    <button type="button" data-open-delete="delete-user-{{ $user->id }}" class="font-semibold text-rose-300 hover:text-rose-200">Delete</button>
                                    <dialog id="delete-user-{{ $user->id }}" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-0 text-white shadow-2xl backdrop:bg-slate-950/80">
                                        <div class="p-6">
                                            <div class="flex items-start gap-4">
                                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-500/10 text-xl text-rose-300" aria-hidden="true">!</span>
                                                <div>
                                                    <h3 class="text-lg font-semibold">Delete user?</h3>
                                                    <p class="mt-2 text-sm leading-6 text-slate-300">You’re about to permanently delete <strong class="text-white">{{ $user->name }}</strong> ({{ $user->email }}). This cannot be undone.</p>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('admin.accounts.destroy', $user) }}" class="mt-6 flex justify-end gap-3">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" data-close-delete class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-800">Cancel</button>
                                                <button class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-500">Delete user</button>
                                            </form>
                                        </div>
                                    </dialog>
                                @endif
                                @if (! auth()->user()->canManage($user, 'edit') && ! auth()->user()->canManage($user, 'delete'))
                                    <span class="text-slate-400">Protected</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>

@endsection
