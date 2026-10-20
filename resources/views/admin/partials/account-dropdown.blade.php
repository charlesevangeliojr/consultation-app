<details class="relative">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-3 hover:border-slate-700">
        <span class="flex min-w-0 items-center gap-3 text-left">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-500/10 text-rose-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0"/></svg>
            </span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-medium text-slate-200">{{ auth()->user()->name }}</span>
                <span class="mt-1 block truncate text-xs text-slate-400">{{ auth()->user()->email }}</span>
            </span>
        </span>
        <svg class="h-5 w-5 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
        <span class="sr-only">Account options</span>
    </summary>
    <div class="absolute bottom-full left-0 z-30 mb-2 w-full rounded-xl border border-slate-700 bg-slate-900 p-2 shadow-xl">
        <a href="{{ route('admin.profile.edit') }}" @class([
            'block rounded-lg px-3 py-2.5 text-sm transition',
            'bg-rose-500/10 text-rose-300' => request()->routeIs('admin.profile.*'),
            'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.profile.*'),
        ]) @if(request()->routeIs('admin.profile.*')) aria-current="page" @endif>My profile</a>
        <a href="{{ route('notifications.index') }}" class="block rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-slate-800">Notifications</a>
        <form method="POST" action="{{ route('admin.logout') }}" class="mt-1 border-t border-slate-800 pt-1">
            @csrf
            <button class="w-full rounded-lg px-3 py-2.5 text-left text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white">Sign out</button>
        </form>
    </div>
</details>
