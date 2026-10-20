@extends($notificationLayout)
@section('title', 'Notifications')
@section('content')
<div class="mx-auto max-w-3xl" data-notification-inbox data-notification-seen-url="{{ route('notifications.read-all') }}" data-notification-through="{{ $openedThrough }}">
    <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-widest text-rose-300">Your account updates</p><h2 class="mt-3 text-3xl font-bold">Notifications</h2><p class="mt-3 text-sm leading-7 text-slate-400">All updates sent to your account, newest first. Other users cannot see your inbox.</p></div><a href="{{ route('notifications.settings') }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-700 px-4 py-3 text-sm font-semibold">Notification settings</a></div>
    @if(session('status'))<p role="status" class="mt-5 rounded-xl border border-emerald-800 p-4 text-sm text-emerald-300">{{ session('status') }}</p>@endif
    @if(!$inboxReady)
        <p role="alert" class="mt-6 rounded-xl border border-amber-700 p-5 text-sm leading-7 text-slate-300">Notification database setup is missing. The school administrator must apply the latest migrations (or the documented phpMyAdmin SQL setup). Existing account and booking records are not deleted.</p>
    @else
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3"><nav aria-label="Notification filters" class="flex gap-2">@foreach(['all' => 'All', 'unread' => 'Unread'] as $value => $label)<a href="{{ route('notifications.index', ['filter' => $value]) }}" @if($filter === $value) aria-current="page" @endif class="inline-flex min-h-11 items-center rounded-lg border border-slate-700 px-4 py-2 text-sm font-semibold">{{ $label }}</a>@endforeach</nav><form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="min-h-11 rounded-lg px-3 py-2 text-sm font-semibold text-rose-300">Mark all as read</button></form></div>
        <p class="mt-4 rounded-xl border border-rose-800 p-4 text-sm text-rose-300" data-notification-new hidden>New notifications are available. <a class="font-semibold underline" href="{{ route('notifications.index', ['filter' => $filter]) }}">Refresh inbox →</a></p>
        <div class="mt-4 space-y-3">
            @forelse($notifications as $notification)
                <article class="rounded-xl border {{ $notification->read_at ? 'border-slate-800' : 'border-rose-700' }} bg-slate-900 p-5" data-notification-record="{{ $notification->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-semibold">{{ $notification->title }}</h3><span data-notification-status class="text-xs {{ $notification->read_at ? 'text-slate-400' : 'text-rose-300' }}">{{ $notification->read_at ? 'Read' : 'Unread' }}</span></div>
                    <p class="mt-3 text-sm leading-7 text-slate-300">{{ $notification->body }}</p>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3"><time class="text-xs text-slate-400" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('M j, Y · g:i A') }} · {{ config('app.timezone') }}</time><form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="min-h-11 rounded-lg px-3 py-2 text-xs font-semibold text-rose-300">View update →</button></form></div>
                </article>
            @empty<div class="rounded-xl border border-slate-800 bg-slate-900 p-6 text-sm leading-7 text-slate-400">{{ $filter === 'unread' ? 'You have no unread notifications.' : 'No notifications yet. New account and consultation updates will appear here.' }}</div>@endforelse
        </div>
        <div class="mt-5">{{ $notifications->links() }}</div>
        <p class="mt-5 text-xs leading-6 text-slate-400">Inbox history starts when this feature is enabled; past events are not reconstructed. Phone/desktop push alerts are optional and configured separately in Notification settings.</p>
    @endif
</div>
@endsection
