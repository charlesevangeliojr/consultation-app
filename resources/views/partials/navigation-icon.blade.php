<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($icon)
        @case('home')<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>@break
        @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2"/>@break
        @case('users')<circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M17 5a3 3 0 0 1 0 6m2 10v-2a6 6 0 0 0-2-4.5"/>@break
        @case('school')<path d="m3 9 9-6 9 6v12H3ZM9 21v-7h6v7M7 10h.01M17 10h.01"/>@break
        @case('monitor')<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M6 13l4-4 3 3 5-5"/>@break
        @case('settings')<path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3" fill="currentColor"/><circle cx="15" cy="17" r="3" fill="currentColor"/>@break
        @case('plus')<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M12 7v10M7 12h10"/>@break
        @default<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
    @endswitch
</svg>
