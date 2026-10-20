<?php

return [
    'enabled' => (bool) env('WEBPUSH_ENABLED', false),
    'public_key' => env('WEBPUSH_PUBLIC_KEY'),
    'private_key' => env('WEBPUSH_PRIVATE_KEY'),
    'subject' => env('WEBPUSH_SUBJECT'),
    // Event-driven synchronous delivery works without a cron/queue worker.
    // Enable queues only on hosting where a real worker can run.
    'queued' => (bool) env('WEBPUSH_QUEUED', false),
    'endpoint_hosts' => ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com', '.notify.windows.com'],
];
