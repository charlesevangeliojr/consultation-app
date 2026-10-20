<?php

namespace App\Support;

use App\Contracts\PushTransport;
use App\Jobs\SendBrowserPush;
use App\Models\PushSubscription;
use App\Models\SiteSetting;
use App\Models\AccountNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BrowserPush
{
    public static function configured(): bool
    {
        return config('webpush.enabled') && filled(config('webpush.public_key'))
            && filled(config('webpush.private_key')) && filled(config('webpush.subject'))
            && Schema::hasTable('push_subscriptions');
    }

    public static function notify(int $userId, string $body, string $url): void
    {
        // Store the inbox event in the booking transaction, even if push is off.
        // Gracefully tolerate code deployment before the hosted migration runs.
        if (Schema::hasTable('account_notifications')) {
            $title = match ($url) {
                '/admin/accounts' => 'Teacher scheduling approval',
                '/consultations/teacher/profile' => 'Scheduling permission update',
                default => 'Consultation update',
            };
            AccountNotification::create(['user_id' => $userId, 'title' => $title, 'body' => $body, 'url' => $url]);
        }
        if (! self::configured()) return;
        // Commit booking changes before notifying; a rolled-back request sends nothing.
        DB::afterCommit(function () use ($userId, $body, $url) {
            try {
                if (config('webpush.queued')) SendBrowserPush::dispatch($userId, $body, $url);
                else SendBrowserPush::dispatchSync($userId, $body, $url);
            } catch (\Throwable) {
                // Never log subscription URLs, credentials, payloads, or user details.
                Log::warning('Browser push dispatch failed; booking data was preserved.');
            }
        });
    }

    public function deliver(int $userId, string $body, string $url, ?string $endpointHash = null): bool
    {
        if (! self::configured()) return false;
        $settings = SiteSetting::current();
        $payload = [
            'title' => $settings->name ?: 'Consultation update',
            'body' => $body,
            'url' => $url,
            'icon' => PwaIcons::url(192, $settings->logo_path),
        ];
        $sent = false;
        $subscriptions = PushSubscription::where('user_id', $userId)
            ->where('vapid_key_hash', hash('sha256', config('webpush.public_key')))
            ->when($endpointHash, fn ($query) => $query->where('endpoint_hash', $endpointHash))->get();
        foreach ($subscriptions as $subscription) {
            try {
                $result = app(PushTransport::class)->send($subscription, $payload);
                if ($result === 'expired') $subscription->delete();
                if ($result === 'sent') $sent = true;
            } catch (\Throwable) {
                Log::warning('Browser push delivery failed; no subscription secrets were logged.');
            }
        }
        return $sent;
    }
}
