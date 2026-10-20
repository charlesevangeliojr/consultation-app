<a href="{{ route('notifications.index') }}" class="notification-bell" data-notification-bell data-notification-summary-url="{{ route('notifications.summary') }}" aria-label="Notifications{{ $notificationUnreadCount ? ', '.$notificationUnreadCount.' unread' : '' }}" title="Notifications">
    @include('partials.navigation-icon', ['icon' => 'bell'])
    <span class="notification-bell-count" data-notification-count="{{ $notificationUnreadCount }}" aria-hidden="true" @if(!$notificationUnreadCount) hidden @endif>{{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}</span>
</a>
