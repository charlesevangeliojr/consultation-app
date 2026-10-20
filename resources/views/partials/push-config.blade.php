@php
    $pushConfigured = \App\Support\BrowserPush::configured();
    $pushDatabaseReady = \Illuminate\Support\Facades\Schema::hasTable('push_subscriptions');
    $pushConfig = [
        'enabled' => $pushConfigured,
        'setupMessage' => $pushDatabaseReady ? 'Push delivery is not configured yet. Ask the school administrator to complete the server setup.' : 'Notification database setup is missing. Ask the school administrator to apply the latest migrations or phpMyAdmin setup SQL.',
        'publicKey' => $pushConfigured ? config('webpush.public_key') : null,
        'userKey' => 'consultation-push-enabled-'.auth()->id(),
        'statusUrl' => route('notifications.status'), 'subscribeUrl' => route('notifications.store'),
        'unsubscribeUrl' => route('notifications.destroy'), 'testUrl' => route('notifications.test'),
    ];
@endphp
<script type="application/json" data-push-config>@json($pushConfig)</script>
