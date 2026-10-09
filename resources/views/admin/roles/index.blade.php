@extends('admin.layout')

@section('title', 'Roles')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-rose-400">User Management</p>
            <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Roles</h2>
            <p class="mt-3 text-slate-400">Reusable permission sets for user accounts.</p>
        </div>
        @if (auth()->user()->canAccess('accounts', 'add'))
            <a href="{{ route('admin.roles.create') }}" class="rounded-lg bg-rose-500 px-5 py-3 font-semibold transition hover:bg-rose-400">Create role</a>
        @endif
    </div>

    @if (session('status'))
        <p role="status" class="mt-6 rounded-lg border border-emerald-900 bg-emerald-950/50 p-4 text-sm text-emerald-200">{{ session('status') }}</p>
    @endif

    <div class="mt-8 overflow-x-auto rounded-2xl border border-slate-800">
        <table class="w-full min-w-[560px] text-left text-sm">
            <thead class="bg-slate-900 text-slate-300">
                <tr><th class="px-5 py-3 font-medium">Role</th><th class="px-5 py-3 font-medium">Permissions</th><th class="px-5 py-3 font-medium">Assigned users</th><th class="px-5 py-3 text-right font-medium">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-800 bg-slate-900/50">
                @forelse ($roles as $role)
                    @php
                        $grants = collect($role->permissions)->flatMap(fn ($actions) => collect($actions)->filter())->count();
                    @endphp
                    <tr class="transition-colors hover:bg-slate-800/60">
                        <td class="px-5 py-4 font-medium">{{ $role->name }}</td>
                        <td class="px-5 py-4 text-slate-300">{{ $grants }} permission{{ $grants === 1 ? '' : 's' }}</td>
                        <td class="px-5 py-4 text-slate-300">{{ $role->users_count }}</td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-4">
                                @if (auth()->user()->canAccess('accounts', 'edit'))
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="font-semibold text-rose-300 hover:text-rose-200">Edit</a>
                                @endif
                                @if (auth()->user()->canAccess('accounts', 'delete') && $role->users_count === 0)
                                    <button type="button" data-open-delete="delete-role-{{ $role->id }}" class="font-semibold text-rose-300 hover:text-rose-200">Delete</button>
                                    <dialog id="delete-role-{{ $role->id }}" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-0 text-white shadow-2xl backdrop:bg-slate-950/80">
                                        <div class="p-6">
                                            <h3 class="text-lg font-semibold">Delete role?</h3>
                                            <p class="mt-2 text-sm leading-6 text-slate-300">Delete <strong class="text-white">{{ $role->name }}</strong>? This cannot be undone.</p>
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="mt-6 flex justify-end gap-3">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" data-close-delete class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-800">Cancel</button>
                                                <button class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-500">Delete role</button>
                                            </form>
                                        </div>
                                    </dialog>
                                @elseif ($role->users_count > 0)
                                    <span class="text-slate-500">In use</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">No roles yet. Create a role to reuse a permission set for accounts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $roles->links() }}</div>
@endsection
