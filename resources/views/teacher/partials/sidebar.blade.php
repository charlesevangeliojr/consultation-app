<aside class="teacher-sidebar" aria-label="Teacher sidebar">
    <a href="{{ route('consultations.home') }}" class="flex min-w-0 items-center gap-3 border-b border-slate-800 px-5 py-5 text-lg font-bold">
        @if($siteSettings->logo_path)<img src="{{ asset('storage/'.$siteSettings->logo_path) }}" alt="" class="h-10 w-10 shrink-0 rounded-lg object-contain">
        @else<span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-rose-500 text-white">{{ mb_substr($siteSettings->name ?: 'C', 0, 1) }}</span>@endif
        <span class="min-w-0 break-words [overflow-wrap:anywhere]">{{ $siteSettings->name ?: 'ConSync' }}<span class="text-rose-400">.</span></span>
    </a>
    <p class="px-5 pt-6 text-xs font-semibold uppercase tracking-widest text-slate-500">Teacher portal</p>
    <nav class="teacher-sidebar-navigation flex-1 space-y-2 px-3 py-4" aria-label="Teacher desktop navigation" data-section-navigation>
        @foreach($items as $item)
            <a href="{{ $item['url'] }}" data-navigation-section="{{ $item['section'] }}" @if($item['active'] ?? false) aria-current="location" @endif>
                @include('partials.navigation-icon', ['icon' => $item['icon']])
                <span>{{ ['Calendar' => 'My calendar', 'Requests' => 'Consultation requests', 'Publish' => 'Publish availability', 'Settings' => 'Consultation settings'][$item['label']] ?? $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
    <div class="border-t border-slate-800 p-4">
        <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
        <p class="mt-1 truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
        <form method="POST" action="{{ route('consultations.logout') }}" class="mt-4">@csrf<button class="min-h-11 w-full rounded-lg border border-slate-700 px-3 text-left text-sm text-slate-300 hover:bg-slate-800">Sign out</button></form>
    </div>
</aside>
