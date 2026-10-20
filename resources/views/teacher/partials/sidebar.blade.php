<aside class="teacher-sidebar" aria-label="Teacher sidebar">
    <a href="{{ route('consultations.home') }}" class="flex min-w-0 items-center gap-3 border-b border-slate-800 px-5 py-5 text-lg font-bold">
        @if($siteSettings->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteSettings->logo_path) }}" alt="" class="h-10 w-10 shrink-0 rounded-lg object-contain">
        @else<span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-rose-500 text-white">{{ mb_substr($siteSettings->name ?: 'C', 0, 1) }}</span>@endif
        <span class="min-w-0 break-words [overflow-wrap:anywhere]">{{ $siteSettings->name ?: 'ConSync' }}<span class="text-rose-400">.</span></span>
    </a>
    <p class="px-5 pt-6 text-xs font-semibold uppercase tracking-widest text-slate-500">Teacher portal</p>
    <nav class="teacher-sidebar-navigation flex-1 space-y-2 px-3 py-4" aria-label="Teacher desktop navigation">
        @foreach($items as $item)
            @continue($item['icon'] === 'profile')
            <a href="{{ $item['url'] }}" @if($item['active'] ?? false) aria-current="page" @endif>
                @include('partials.navigation-icon', ['icon' => $item['icon']])
                <span>{{ $item['desktopLabel'] ?? $item['label'] }}</span>
                @include('partials.navigation-badge', ['mobileBadge' => false])
            </a>
        @endforeach
    </nav>
    <div class="shrink-0 border-t border-slate-800 p-3">
        <details class="teacher-account-menu" data-teacher-account-menu>
            <summary aria-label="Account options" @class(['teacher-account-trigger', 'is-current' => request()->routeIs('consultations.teacher-profile.*')])>
                @include('teacher.partials.avatar', ['avatarSize' => 'h-10 w-10'])
                <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold">{{ auth()->user()->name }}</span><span class="mt-1 block truncate text-xs text-slate-400">{{ auth()->user()->email }}</span></span>
                <svg class="teacher-account-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 14 5-5 5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </summary>
            <nav class="teacher-account-options" aria-label="Teacher account navigation">
                <p class="px-3 pb-2 pt-1 text-[10px] font-semibold uppercase tracking-widest text-slate-500">Your account</p>
                <a href="{{ route('consultations.teacher-profile.edit') }}" @if(request()->routeIs('consultations.teacher-profile.*')) aria-current="page" @endif>
                    @include('partials.navigation-icon', ['icon' => 'profile'])<span>My profile</span>
                    @include('partials.navigation-badge', ['item' => collect($items)->firstWhere('icon', 'profile') ?? [], 'mobileBadge' => false])
                </a>
                <div class="my-1 border-t border-slate-700"></div>
                <a href="{{ route('notifications.index') }}">@include('partials.navigation-icon', ['icon' => 'bell'])<span>Notifications</span></a>
                <form method="POST" action="{{ route('consultations.logout') }}">
                    @csrf
                    <button type="submit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4H5v16h4M9 12h12m-4-4 4 4-4 4"/></svg><span>Sign out</span></button>
                </form>
            </nav>
        </details>
    </div>
</aside>
